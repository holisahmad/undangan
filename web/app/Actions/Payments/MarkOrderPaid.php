<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Actions\Entitlements\GrantInvitationEntitlement;
use App\Data\Payments\MarkPaidResult;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ServiceEntitlement;
use App\Models\User;
use App\Notifications\OrderPaidNotification;
use App\Support\Audit\AuditLogger;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * The single place that turns an order into a paid order. Both the signed
 * provider notification and the manual bank-transfer verification end here, so
 * entitlement stacking, idempotency and the confirmation email cannot diverge
 * between payment methods (docs/PAYMENT_FLOW.md, sections 4 and 5).
 */
final class MarkOrderPaid
{
    public function __construct(
        private readonly GrantInvitationEntitlement $grant,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, scalar|null>  $metadata
     */
    public function handle(
        Order $order,
        string $provider,
        ?string $providerTransactionId,
        int $amount,
        string $currency,
        ?CarbonInterface $paidAt = null,
        ?User $actor = null,
        array $metadata = [],
    ): MarkPaidResult {
        $paidOrder = null;

        $result = DB::transaction(function () use (
            $order,
            $provider,
            $providerTransactionId,
            $amount,
            $currency,
            $paidAt,
            $actor,
            $metadata,
            &$paidOrder,
        ): MarkPaidResult {
            $locked = Order::query()
                ->lockForUpdate()
                ->with(['items.plan', 'wedding', 'user'])
                ->find($order->getKey());

            if ($locked === null) {
                return new MarkPaidResult(newlyPaid: false);
            }

            if ($locked->isPaid()) {
                // A second notification or a double-clicked verification must
                // never create a second entitlement.
                $this->audit->log(
                    action: 'payment.duplicate',
                    resourceType: 'order',
                    resourceId: $locked->getKey(),
                    actor: $actor,
                    metadata: ['reason' => 'order_already_paid'] + $metadata,
                );

                return new MarkPaidResult(newlyPaid: false, duplicate: true);
            }

            $settledAt = $paidAt ?? now();

            $payment = Payment::query()->firstOrCreate(
                ['idempotency_key' => 'order:'.$locked->getKey()],
                [
                    'order_id' => $locked->getKey(),
                    'provider' => $provider,
                    'amount' => $amount,
                    'currency' => $currency,
                    'status' => PaymentStatus::Initiated,
                ],
            );

            $payment->forceFill([
                'provider' => $provider,
                'provider_transaction_id' => $providerTransactionId,
                'status' => PaymentStatus::Succeeded,
                'amount' => $amount,
                'currency' => $currency,
                'paid_at' => $settledAt,
            ])->save();

            $locked->forceFill([
                'status' => OrderStatus::Paid,
                'paid_at' => $settledAt,
            ])->save();

            // One entitlement per order item: keeping the whole list means an
            // order with several items cannot lose the earlier grants in the
            // audit trail and the returned result.
            $entitlements = [];

            foreach ($locked->items as $item) {
                $alreadyGranted = ServiceEntitlement::query()
                    ->where('order_item_id', $item->getKey())
                    ->exists();

                if ($alreadyGranted) {
                    continue;
                }

                $entitlements[] = $this->grant->handle($locked->wedding, null, null, $item);
            }

            $this->audit->log(
                action: 'order.paid',
                resourceType: 'order',
                resourceId: $locked->getKey(),
                actor: $actor ?? $locked->user,
                metadata: [
                    'public_order_number' => $locked->public_order_number,
                    'payment_id' => $payment->getKey(),
                    'payment_provider' => $payment->provider,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'entitlement_ids' => implode(',', array_map(
                        static fn (ServiceEntitlement $entitlement) => (string) $entitlement->getKey(),
                        $entitlements,
                    )),
                ] + $metadata,
            );

            $paidOrder = $locked;

            return new MarkPaidResult(newlyPaid: true, entitlements: $entitlements);
        });

        // Sent after the transaction commits so the email never announces a
        // payment that was rolled back.
        $this->notifyOwner($paidOrder);

        return $result;
    }

    private function notifyOwner(?Order $order): void
    {
        if ($order?->user === null) {
            return;
        }

        try {
            $order->user->notify(new OrderPaidNotification($order));
        } catch (Throwable $exception) {
            // The order is already paid, so a mail outage must not fail the
            // caller and invite the provider to retry.
            report($exception);
        }
    }
}
