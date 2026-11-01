<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Data\Payments\MarkPaidResult;
use App\Enums\PaymentConfirmationStatus;
use App\Models\Order;
use App\Models\PaymentConfirmation;
use App\Models\User;
use App\Support\Audit\AuditLogger;
use App\Support\Payments\BankTransfer;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Operator decision on a transfer proof. Verification settles the order through
 * MarkOrderPaid, the same action used by signed provider notifications, so the
 * entitlement and audit trail are identical regardless of payment method.
 */
final class ReviewPaymentConfirmation
{
    public function __construct(
        private readonly MarkOrderPaid $settle,
        private readonly AuditLogger $audit,
    ) {}

    public function verify(PaymentConfirmation $confirmation, User $reviewer): MarkPaidResult
    {
        return DB::transaction(function () use ($confirmation, $reviewer): MarkPaidResult {
            $locked = $this->lockOpen($confirmation);

            $order = Order::query()->find($locked->order_id);

            if ($order === null) {
                throw new InvalidArgumentException('Pesanan untuk konfirmasi ini tidak ditemukan.');
            }

            if ($locked->amount !== $order->amountDue()) {
                throw new InvalidArgumentException('Nominal konfirmasi tidak sama dengan total tagihan pesanan.');
            }

            $result = $this->settle->handle(
                order: $order,
                provider: BankTransfer::PROVIDER,
                providerTransactionId: 'BT-'.$order->public_order_number,
                amount: $locked->amount,
                currency: $order->currency,
                paidAt: now(),
                actor: $reviewer,
                metadata: [
                    'provider_event_id' => 'confirmation:'.$locked->getKey(),
                    'confirmation_id' => $locked->getKey(),
                ],
            );

            $locked->forceFill([
                'status' => PaymentConfirmationStatus::Verified,
                'reviewed_by' => $reviewer->getKey(),
                'reviewed_at' => now(),
            ])->save();

            $this->audit->log(
                action: 'payment.confirmation_verified',
                resourceType: 'order',
                resourceId: $order->getKey(),
                actor: $reviewer,
                metadata: [
                    'confirmation_id' => $locked->getKey(),
                    'public_order_number' => $order->public_order_number,
                    'amount' => $locked->amount,
                    'already_paid' => ! $result->newlyPaid,
                ],
            );

            return $result;
        });
    }

    public function reject(PaymentConfirmation $confirmation, User $reviewer, string $note): PaymentConfirmation
    {
        return DB::transaction(function () use ($confirmation, $reviewer, $note): PaymentConfirmation {
            $locked = $this->lockOpen($confirmation);

            $locked->forceFill([
                'status' => PaymentConfirmationStatus::Rejected,
                'reviewed_by' => $reviewer->getKey(),
                'reviewed_at' => now(),
                'review_note' => $note,
            ])->save();

            $this->audit->log(
                action: 'payment.confirmation_rejected',
                resourceType: 'order',
                resourceId: $locked->order_id,
                actor: $reviewer,
                metadata: [
                    'confirmation_id' => $locked->getKey(),
                    'note' => $note,
                ],
                result: 'failure',
            );

            return $locked;
        });
    }

    private function lockOpen(PaymentConfirmation $confirmation): PaymentConfirmation
    {
        $locked = PaymentConfirmation::query()->lockForUpdate()->find($confirmation->getKey());

        if ($locked === null) {
            throw new InvalidArgumentException('Konfirmasi pembayaran tidak ditemukan.');
        }

        if ($locked->status !== PaymentConfirmationStatus::Submitted) {
            throw new InvalidArgumentException('Konfirmasi ini sudah ditinjau sebelumnya.');
        }

        return $locked;
    }
}
