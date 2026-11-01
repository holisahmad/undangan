<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Replaces the framework's verification email with the same branded shell as
 * the other transactional mail. The signed URL is still produced by the parent
 * so the route, hash, and expiry window stay in one place.
 */
final class VerifyEmailNotification extends BaseVerifyEmail
{
    /**
     * The signature intentionally mirrors the parent: it declares no parameter
     * type, and PHP rejects narrowing it to object in a child class.
     *
     * @param  object  $notifiable
     */
    public function toMail($notifiable): MailMessage|Mailable
    {
        $url = $this->verificationUrl($notifiable);

        if (self::$toMailCallback !== null) {
            return (self::$toMailCallback)($notifiable, $url);
        }

        $minutes = (int) config('auth.verification.expire', 60);

        return (new MailMessage)
            ->subject('Konfirmasi email Anda — '.config('app.name'))
            ->view(
                ['html' => 'mail.notifications.verify-email', 'text' => 'mail.notifications.verify-email-text'],
                [
                    'name' => (string) (data_get($notifiable, 'name') ?? 'Bapak/Ibu'),
                    'email' => (string) data_get($notifiable, 'email', ''),
                    'url' => $url,
                    'expiresIn' => $minutes,
                    'expiresAt' => now()->addMinutes($minutes)->setTimezone('Asia/Jakarta')
                        ->locale(app()->getLocale())->translatedFormat('l, d F Y · H:i').' WIB',
                ],
            );
    }
}
