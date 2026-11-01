<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Data\Payments\PaymentNotification;
use App\Enums\OrderStatus;
use App\Enums\PaymentEventStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\Payments\InvalidPaymentSignature;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Payments\PaymentGatewayRegistry;
use App\Support\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

/**
 * Verifies signature, order reference, amount and currency for provider
 * notifications before handing the settlement to MarkOrderPaid. Duplicates are
 * discarded via the unique provider event id (docs/PAYMENT_FLOW.md, 3 and 4).
 */
final class ProcessPaymentNotification
{
    public function __construct(
        private readonly PaymentGatewayRegistry $gateways,
        private readonly MarkOrderPaid $settle,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws InvalidPaymentSignature
     */
    public function handle(
        string $provider,
        string $rawPayload,
        ?string $signature,
        array $payload,
    ): PaymentEvent {
        $gateway = $this->gateways->resolve($provider);

        if (! $gateway->verifySignature($rawPayload, $signature)) {
            throw InvalidPaymentSignature::for($provider);
        }

        $notification = $gateway->parseNotification($payload);

        $event = PaymentEvent::query()->firstOrCreate(
            [
                'provider' => $provider,
                'provider_event_id' => $notification->eventId,
            ],
            [
                'event_type' => $notification->eventType,
                'received_at' => now(),
                'processing_status' => PaymentEventStatus::Received,
                'payload' => $notification->toAuditMetadata(),
            ],
        );

        if (! $event->wasRecentlyCreated && $event->processing_status !== PaymentEventStatus::Failed) {
            return $event;
        }

        return $this->apply($event, $notification);
    }

    private function apply(PaymentEvent $event, PaymentNotification $notification): PaymentEvent
    {
        $order = Order::query()
            ->where('public_order_number', $notification->orderNumber)
            ->first();

        if ($order === null) {
            $event->markFailed();

            $this->audit->log(
                action: 'payment.rejected',
                resourceType: 'payment_event',
                resourceId: $event->getKey(),
                actor: null,
                metadata: ['reason' => 'unknown_order'] + $notification->toAuditMetadata(),
                result: 'failure',
            );

            return $event;
        }

        if ($notification->isSucceeded()) {
            return $this->applySucceeded($event, $order, $notification);
        }

        if ($notification->isFailure()) {
            return $this->applyFailure($event, $order, $notification);
        }

        // Pending and unrecognised statuses never activate a service.
        $event->markIgnored();

        return $event;
    }

    private function applySucceeded(PaymentEvent $event, Order $order, PaymentNotification $notification): PaymentEvent
    {
        if ($notification->amount !== $order->total_amount || $notification->currency !== $order->currency) {
            $event->markFailed();

            $this->audit->log(
                action: 'payment.rejected',
                resourceType: 'order',
                resourceId: $order->getKey(),
                actor: null,
                metadata: [
                    'reason' => 'amount_or_currency_mismatch',
                    'expected_amount' => $order->total_amount,
                    'received_amount' => $notification->amount,
                    'expected_currency' => $order->currency,
                    'received_currency' => $notification->currency,
                ] + $notification->toAuditMetadata(),
                result: 'failure',
            );

            return $event;
        }

        $this->settle->handle(
            order: $order,
            provider: $notification->provider,
            providerTransactionId: $notification->providerTransactionId,
            amount: $notification->amount,
            currency: $notification->currency,
            paidAt: $notification->paidAt,
            actor: null,
            metadata: ['provider_event_id' => $notification->eventId],
        );

        $event->markProcessed();

        return $event;
    }

    private function applyFailure(PaymentEvent $event, Order $order, PaymentNotification $notification): PaymentEvent
    {
        return DB::transaction(function () use ($event, $order, $notification): PaymentEvent {
            $locked = Order::query()->lockForUpdate()->find($order->getKey());

            if ($locked === null) {
                $event->markFailed();

                return $event;
            }

            if ($locked->isPaid()) {
                $event->markProcessed();

                return $event;
            }

            $payment = Payment::query()->firstOrCreate(
                ['idempotency_key' => 'order:'.$locked->getKey()],
                [
                    'order_id' => $locked->getKey(),
                    'provider' => $notification->provider,
                    'amount' => $locked->total_amount,
                    'currency' => $locked->currency,
                ],
            );

            $payment->forceFill([
                'provider_transaction_id' => $notification->providerTransactionId,
                'status' => $notification->status === 'expired'
                    ? PaymentStatus::Expired
                    : PaymentStatus::Failed,
            ])->save();

            $locked->forceFill(['status' => OrderStatus::PaymentFailed])->save();

            $this->audit->log(
                action: 'payment.failed',
                resourceType: 'order',
                resourceId: $locked->getKey(),
                actor: null,
                metadata: ['status' => $notification->status] + $notification->toAuditMetadata(),
            );

            $event->markProcessed();

            return $event;
        });
    }
}
