<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Guest;
use App\Models\Wedding;
use App\Support\Invitations\InvitationCopy;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Nudges a guest who has not answered yet. The mail carries a freshly issued
 * personal link because only its hash is stored; nothing is tracked beyond the
 * "reminded at" timestamp (docs/PRD.md: no delivery or read receipts).
 */
final class RsvpReminderNotification extends Notification
{
    public function __construct(
        private readonly Wedding $wedding,
        private readonly Guest $guest,
        private readonly string $invitationUrl,
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
        $couple = InvitationCopy::couple($this->wedding);
        $event = InvitationCopy::eventSummary($this->wedding);

        $rows = [['label' => 'Pernikahan', 'value' => $couple]];

        if ($event?->starts_at !== null) {
            $rows[] = [
                'label' => 'Acara',
                'value' => $event->title.' · '.$event->starts_at->copy()
                    ->setTimezone($event->timezone ?: 'Asia/Jakarta')
                    ->locale(app()->getLocale())
                    ->translatedFormat('l, d F Y · H:i'),
            ];
        }

        if (filled($event?->venue_name)) {
            $rows[] = ['label' => 'Lokasi', 'value' => (string) $event->venue_name];
        }

        return (new MailMessage)
            ->subject('Konfirmasi kehadiran — '.$couple)
            ->view(
                ['html' => 'mail.notifications.rsvp-reminder', 'text' => 'mail.notifications.rsvp-reminder-text'],
                [
                    'guestName' => (string) $this->guest->display_name,
                    'couple' => $couple,
                    'rows' => $rows,
                    'url' => $this->invitationUrl,
                ],
            );
    }
}
