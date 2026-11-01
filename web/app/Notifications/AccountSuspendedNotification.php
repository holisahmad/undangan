<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Support\Contact\PhoneNumber;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the owner why sign-in stopped working. The reason is the same text the
 * operator entered and that is stored in the audit log.
 */
final class AccountSuspendedNotification extends Notification
{
    public function __construct(private readonly string $reason) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $phone = PhoneNumber::display((string) config('site.contact.phone'));
        $whatsapp = PhoneNumber::whatsappLink((string) config('site.contact.whatsapp'));
        $supportUrl = $whatsapp !== null
            ? $whatsapp.'?text='.rawurlencode('Halo, saya ingin meninjau status akun saya.')
            : 'mailto:'.(string) config('site.contact.email');

        return (new MailMessage)
            ->subject('Akun dinonaktifkan sementara — '.config('app.name'))
            ->view(
                ['html' => 'mail.notifications.account-suspended', 'text' => 'mail.notifications.account-suspended-text'],
                [
                    'name' => (string) (data_get($notifiable, 'name') ?? 'Bapak/Ibu'),
                    'reason' => $this->reason,
                    'when' => now()->setTimezone('Asia/Jakarta')->locale(app()->getLocale())->translatedFormat('l, d F Y · H:i').' WIB',
                    'supportUrl' => $supportUrl,
                    'phone' => $phone,
                ],
            );
    }
}
