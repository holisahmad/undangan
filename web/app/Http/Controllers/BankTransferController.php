<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Payments\SubmitPaymentConfirmation;
use App\Enums\PaymentConfirmationStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\Payments\PaymentConfirmationPending;
use App\Exceptions\Payments\PaymentWindowClosed;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentConfirmation;
use App\Notifications\PaymentProofSubmitted;
use App\Support\Audit\AuditLogger;
use App\Support\Payments\BankTransfer;
use App\Support\Payments\PaymentWindow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;
use Throwable;

/**
 * Manual bank-transfer instructions and proof upload. The page never claims a
 * payment succeeded: only an operator decision does that
 * (docs/PAYMENT_FLOW.md, sections 3-5).
 */
final class BankTransferController extends Controller
{
    public function show(Order $order, PaymentWindow $window): View|RedirectResponse
    {
        $this->authorize('pay', $order);

        if ($order->isPaid()) {
            return redirect()
                ->route('orders.show', $order)
                ->with('status', 'Pesanan ini sudah dibayar.');
        }

        if ($order->unique_code === null || $order->payment_expires_at === null) {
            $window->open($order);
        }

        $order->refresh()->load(['items.plan', 'wedding', 'payments', 'confirmations.submittedBy']);

        return view('checkout.transfer', [
            'order' => $order,
            'accounts' => BankTransfer::accounts(),
            'confirmation' => $order->latestConfirmation(),
            'payment' => $order->payments->sortByDesc('created_at')->first(),
        ]);
    }

    public function confirm(
        Request $request,
        Order $order,
        SubmitPaymentConfirmation $submit,
    ): RedirectResponse {
        $this->authorize('pay', $order);

        if ($order->isPaid()) {
            return redirect()
                ->route('orders.show', $order)
                ->with('status', 'Pesanan ini sudah dibayar.');
        }

        $mimes = implode(',', array_map('strval', (array) config('platform.payments.bank_transfer.proof_mimes', ['jpg', 'jpeg', 'png', 'pdf'])));
        $maxKb = (int) config('platform.payments.bank_transfer.proof_max_kb', 4096);

        $validated = $request->validate([
            'sender_name' => ['required', 'string', 'max:120'],
            'sender_bank' => ['required', 'string', 'max:60'],
            'transferred_at' => [
                'required',
                'date',
                'before_or_equal:today',
                'after_or_equal:'.$order->created_at?->copy()->subDay()->toDateString(),
            ],
            // The nominal is frozen server-side; a manipulated value is rejected
            // instead of being trusted.
            'amount' => ['required', 'integer', Rule::in([(string) $order->amountDue()])],
            'note' => ['nullable', 'string', 'max:500'],
            'proof' => ['required', 'file', 'mimes:'.$mimes, 'max:'.$maxKb],
        ], [
            'amount.in' => 'Nominal harus sama dengan total tagihan, termasuk kode unik.',
            'proof.mimes' => 'Bukti transfer harus berupa JPG, PNG, atau PDF.',
            'proof.max' => 'Ukuran bukti transfer melebihi batas '.$maxKb.' KB.',
        ]);

        $file = $request->file('proof');
        $disk = (string) config('platform.payments.bank_transfer.proof_disk', 'local');
        $path = $file->store('payment-proofs/'.$order->getKey(), $disk);

        try {
            $confirmation = $submit->handle(
                order: $order,
                actor: $request->user(),
                data: [
                    'sender_name' => $validated['sender_name'],
                    'sender_bank' => $validated['sender_bank'],
                    'transferred_at' => $validated['transferred_at'],
                    'amount' => (int) $validated['amount'],
                    'note' => $validated['note'] ?? null,
                ],
                proof: [
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ],
            );
        } catch (PaymentWindowClosed|PaymentConfirmationPending|InvalidArgumentException $exception) {
            Storage::disk($disk)->delete($path);

            return redirect()
                ->route('checkout.transfer.show', $order)
                ->withErrors(['proof' => $exception->getMessage()]);
        }

        $this->notifyStudio($order, $confirmation);

        return redirect()
            ->route('orders.show', $order)
            ->with('status', 'Bukti transfer diterima. Undangan aktif setelah admin memverifikasi dana masuk.');
    }

    /**
     * The proof is already stored, so a mail outage must not turn a successful
     * upload into an error page — the admin panel still shows the confirmation.
     */
    private function notifyStudio(Order $order, PaymentConfirmation $confirmation): void
    {
        $email = (string) config('site.contact.email');

        if ($email === '') {
            return;
        }

        try {
            Notification::route('mail', $email)
                ->notify(new PaymentProofSubmitted($order, $confirmation));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /**
     * Reopens a closed window with a fresh unique code. The previous nominal is
     * never reused, so a late transfer cannot be mistaken for the new one. Any
     * proof still awaiting review belongs to that old nominal and is superseded
     * in the same step, otherwise the order would end up in a state where the
     * owner cannot upload a new proof and the operator cannot verify the old one
     * (docs/adr/011-manual-bank-transfer-payments.md).
     */
    public function regenerate(
        Request $request,
        Order $order,
        PaymentWindow $window,
        AuditLogger $audit,
    ): RedirectResponse {
        $this->authorize('pay', $order);

        if ($order->isPaid()) {
            return redirect()
                ->route('orders.show', $order)
                ->with('status', 'Pesanan ini sudah dibayar.');
        }

        if (! $order->paymentWindowClosed()) {
            return redirect()
                ->route('checkout.transfer.show', $order)
                ->withErrors(['payment' => 'Batas waktu pembayaran sebelumnya belum berakhir.']);
        }

        $previousCode = $order->unique_code;

        $superseded = DB::transaction(function () use ($order, $window): int {
            $window->open($order);

            Payment::query()
                ->where('order_id', $order->getKey())
                ->whereIn('status', [PaymentStatus::Pending->value, PaymentStatus::Initiated->value, PaymentStatus::Expired->value])
                ->update([
                    'status' => PaymentStatus::Pending->value,
                    'provider' => BankTransfer::PROVIDER,
                    'amount' => $order->amountDue(),
                ]);

            // Rejected rather than deleted: the claim and its file stay readable
            // for the audit trail, while the queue and the upload form unblock.
            return PaymentConfirmation::query()
                ->where('order_id', $order->getKey())
                ->where('status', PaymentConfirmationStatus::Submitted->value)
                ->update([
                    'status' => PaymentConfirmationStatus::Rejected->value,
                    'reviewed_at' => now(),
                    'review_note' => 'Kode pembayaran baru diterbitkan sebelum bukti ini ditinjau.',
                ]);
        });

        $audit->log(
            action: 'payment.code_regenerated',
            resourceType: 'order',
            resourceId: $order->getKey(),
            actor: $request->user(),
            metadata: [
                'public_order_number' => $order->public_order_number,
                'previous_code' => $previousCode,
                'unique_code' => $order->unique_code,
                'payment_expires_at' => $order->payment_expires_at?->toIso8601String(),
                'amount_due' => $order->amountDue(),
                'superseded_confirmations' => $superseded,
            ],
        );

        return redirect()
            ->route('checkout.transfer.show', $order)
            ->with('status', $superseded > 0
                ? 'Kode pembayaran baru dibuat. Bukti transfer lama dibatalkan karena nominalnya tidak lagi berlaku; unggah bukti untuk nominal yang baru.'
                : 'Kode pembayaran baru dibuat. Batas waktu diperpanjang.');
    }
}
