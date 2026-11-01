<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Order;
use App\Support\Invitations\InvitationCopy;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Confirms a verified payment to the customer. It carries no payment
 * credentials, only the public order number and the amount already stored on
 * the order. The body lives in mail.notifications.order-paid so a confirmation
 * reads like the rest of the product rather than a framework default.
 */
final class OrderPaidNotification extends Notification
{
    public function __construct(private readonly Order $order) {}

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
        $wedding = $order->wedding;
        $couple = $wedding !== null ? InvitationCopy::couple($wedding) : 'undangan Anda';

        // Timestamps are stored in UTC; the customer reads them in WIB.
        $paidAt = ($order->paid_at ?? now())->copy()->setTimezone('Asia/Jakarta');

        return (new MailMessage)
            ->subject('Pembayaran diterima — '.$order->public_order_number)
            ->view(
                ['html' => 'mail.notifications.order-paid', 'text' => 'mail.notifications.order-paid-text'],
                [
                    'name' => (string) (data_get($notifiable, 'name') ?? 'Bapak/Ibu'),
                    'number' => (string) $order->public_order_number,
                    'couple' => $couple,
                    'total' => $order->totalLabel(),
                    'paidAt' => $paidAt->locale(app()->getLocale())->translatedFormat('l, d F Y · H:i').' WIB',
                    'url' => route('orders.show', $order),
                ],
            );
    }
}
