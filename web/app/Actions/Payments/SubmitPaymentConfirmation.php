<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Enums\PaymentConfirmationStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\Payments\PaymentConfirmationPending;
use App\Exceptions\Payments\PaymentWindowClosed;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentConfirmation;
use App\Models\User;
use App\Support\Audit\AuditLogger;
use App\Support\Payments\BankTransfer;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Records the customer's transfer claim together with the uploaded proof. The
 * order is never marked paid here: an operator still has to match the transfer
 * against the bank statement (docs/PAYMENT_FLOW.md, sections 3 and 5).
 */
final class SubmitPaymentConfirmation
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array{sender_name: string, sender_bank: string, transferred_at: string, amount: int, note: ?string}  $data
     * @param  array{path: string, original_name: ?string, mime: ?string, size: ?int}  $proof
     */
    public function handle(Order $order, User $actor, array $data, array $proof): PaymentConfirmation
    {
        if (! $order->hasOpenPaymentWindow()) {
            throw PaymentWindowClosed::create();
        }

        if ($data['amount'] !== $order->amountDue()) {
            throw new InvalidArgumentException('Nominal transfer harus sama dengan total tagihan, termasuk kode unik.');
        }

        return DB::transaction(function () use ($order, $actor, $data, $proof): PaymentConfirmation {
            $locked = Order::query()->lockForUpdate()->find($order->getKey());

            if ($locked === null) {
                throw new InvalidArgumentException('Pesanan tidak ditemukan.');
            }

            if ($locked->isPaid()) {
                throw new InvalidArgumentException('Pesanan ini sudah dibayar.');
            }

            if ($locked->confirmations()->where('status', PaymentConfirmationStatus::Submitted->value)->exists()) {
                throw PaymentConfirmationPending::create();
            }

            $payment = Payment::query()->firstOrCreate(
                ['idempotency_key' => 'order:'.$locked->getKey()],
                [
                    'order_id' => $locked->getKey(),
                    'provider' => BankTransfer::PROVIDER,
                    'amount' => $locked->amountDue(),
                    'currency' => $locked->currency,
                    'status' => PaymentStatus::Pending,
                ],
            );

            if (! $payment->isSucceeded()) {
                $payment->forceFill([
                    'provider' => BankTransfer::PROVIDER,
                    'amount' => $locked->amountDue(),
                    'status' => PaymentStatus::Pending,
                ])->save();
            }

            $confirmation = PaymentConfirmation::create([
                'order_id' => $locked->getKey(),
                'payment_id' => $payment->getKey(),
                'submitted_by' => $actor->getKey(),
                'sender_name' => $data['sender_name'],
                'sender_bank' => $data['sender_bank'],
                'transferred_at' => $data['transferred_at'],
                'amount' => $data['amount'],
                'note' => $data['note'],
                'proof_path' => $proof['path'],
                'proof_original_name' => $proof['original_name'],
                'proof_mime' => $proof['mime'],
                'proof_size' => $proof['size'],
                'status' => PaymentConfirmationStatus::Submitted,
            ]);

            $this->audit->log(
                action: 'payment.confirmation_submitted',
                resourceType: 'order',
                resourceId: $locked->getKey(),
                actor: $actor,
                metadata: [
                    'confirmation_id' => $confirmation->getKey(),
                    'public_order_number' => $locked->public_order_number,
                    'amount' => $confirmation->amount,
                    'sender_bank' => $confirmation->sender_bank,
                ],
            );

            return $confirmation;
        });
    }
}
