<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Order;
use App\Models\PaymentConfirmation;
use App\Support\Invitations\InvitationCopy;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A customer has uploaded a transfer proof. Nothing changes in the order at that
 * moment — only an operator decision does — so this mail is the one signal that
 * money may be sitting in the account (docs/PAYMENT_FLOW.md, sections 3-5).
 */
final class PaymentProofSubmitted extends Notification
{
    public function __construct(
        private readonly Order $order,
        private readonly PaymentConfirmation $confirmation,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order;
        $confirmation = $this->confirmation;
        $customer = $order->user;

        // Timestamps are stored in UTC; the studio reads them in WIB.
        $receivedAt = ($confirmation->created_at ?? now())->copy()->setTimezone('Asia/Jakarta');
        $transferredAt = $confirmation->transferred_at?->copy()->setTimezone('Asia/Jakarta');
        $couple = $order->wedding !== null ? InvitationCopy::couple($order->wedding) : null;

        $rows = [
            ['label' => 'Pesanan', 'value' => (string) $order->public_order_number],
        ];

        if (filled($couple)) {
            $rows[] = ['label' => 'Pasangan', 'value' => (string) $couple];
        }

        $rows = array_merge($rows, [
            ['label' => 'Nominal transfer', 'value' => $confirmation->amountLabel().' (termasuk kode unik)'],
            ['label' => 'Bank pengirim', 'value' => (string) $confirmation->sender_bank],
            ['label' => 'Nama pengirim', 'value' => (string) $confirmation->sender_name],
            [
                'label' => 'Tanggal transfer',
                'value' => $transferredAt !== null
                    ? $transferredAt->locale(app()->getLocale())->translatedFormat('l, d F Y')
                    : 'tidak disebutkan',
            ],
            ['label' => 'Bukti', 'value' => (string) ($confirmation->proof_original_name ?? 'berkas terunggah')],
        ]);

        if ($customer !== null) {
            $rows[] = ['label' => 'Pelanggan', 'value' => (string) $customer->email, 'href' => 'mailto:'.$customer->email];
        }

        return (new MailMessage)
            ->subject('Bukti transfer — '.$order->public_order_number)
            ->view(
                ['html' => 'mail.notifications.payment-proof', 'text' => 'mail.notifications.payment-proof-text'],
                [
                    'number' => (string) $order->public_order_number,
                    'senderName' => (string) $confirmation->sender_name,
                    'rows' => $rows,
                    'amount' => $confirmation->amountLabel(),
                    // Not "message": Laravel injects the Illuminate\Mail\Message
                    // under that key before the view renders.
                    'proofNote' => filled($confirmation->note) ? (string) $confirmation->note : null,
                    'adminUrl' => route('admin.orders.show', $order),
                    'replyUrl' => $customer !== null
                        ? 'mailto:'.$customer->email.'?subject='.rawurlencode('Pesanan '.$order->public_order_number.' — '.config('app.name'))
                        : null,
                    'receivedAt' => $receivedAt->locale(app()->getLocale())->translatedFormat('l, d F Y · H:i').' WIB',
                ],
            );
    }
}
