<?php

declare(strict_types=1);

namespace App\Actions\Guests;

use App\Models\Guest;
use App\Models\User;
use App\Models\Wedding;
use App\Notifications\RsvpReminderNotification;
use App\Support\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Emails guests who supplied an address and have not answered yet. A fresh
 * personal link is issued per reminder because only its hash is stored; the
 * previous link is revoked by the same action, which is already the product
 * rule for re-issuing (docs/DATABASE_SCHEMA.md, section 6).
 */
final class SendRsvpReminders
{
    public function __construct(
        private readonly IssueGuestInvitationLink $issueLink,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * How many guests a run would message, without sending anything.
     */
    public function candidates(Wedding $wedding, bool $force = false): int
    {
        return (int) $this->pendingQuery($wedding, $force)->count();
    }

    /**
     * Reminders only make sense while the invitation is actually being served.
     *
     * @return array{eligible: int, sent: int, failed: int, unavailable: bool}
     */
    public function handle(Wedding $wedding, ?User $actor = null, bool $force = false, ?int $limit = null): array
    {
        if ($wedding->activePublication()->first() === null) {
            return ['eligible' => 0, 'sent' => 0, 'failed' => 0, 'unavailable' => true];
        }

        $limit ??= (int) config('platform.guests.reminder_batch_limit', 200);

        $guests = $this->pendingQuery($wedding, $force)->limit($limit)->get();
        $sent = 0;
        $failed = 0;

        foreach ($guests as $guest) {
            try {
                $result = $this->issueLink->handle($guest, $actor);

                Notification::route('mail', $guest->contact_email)->notify(
                    new RsvpReminderNotification(
                        $wedding,
                        $guest,
                        route('guests.invitation.show', ['token' => $result['token']]),
                    ),
                );

                // Stamped only after the mail went out, so a failed send is
                // retried on the next run instead of being counted as done.
                $guest->forceFill(['rsvp_reminded_at' => now()])->save();
                $sent++;
            } catch (Throwable $exception) {
                $failed++;
                report($exception);
            }
        }

        if ($sent > 0 || $failed > 0) {
            $this->audit->log(
                action: 'guest.reminders_sent',
                resourceType: 'wedding',
                resourceId: $wedding->getKey(),
                actor: $actor,
                metadata: ['sent' => $sent, 'failed' => $failed],
            );
        }

        return ['eligible' => $guests->count(), 'sent' => $sent, 'failed' => $failed, 'unavailable' => false];
    }

    /**
     * @return Builder<Guest>
     */
    private function pendingQuery(Wedding $wedding, bool $force)
    {
        $cooldownDays = (int) config('platform.guests.reminder_cooldown_days', 7);

        return $wedding->guests()
            ->whereNotNull('contact_email')
            ->whereDoesntHave('rsvp')
            ->when(! $force, static function ($query) use ($cooldownDays): void {
                $query->where(static function ($inner) use ($cooldownDays): void {
                    $inner->whereNull('rsvp_reminded_at')
                        ->orWhere('rsvp_reminded_at', '<=', now()->subDays($cooldownDays));
                });
            })
            ->orderBy('display_name');
    }
}
