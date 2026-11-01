<?php

declare(strict_types=1);

namespace App\Actions\Guests;

use App\Actions\Analytics\TrackEvent;
use App\Enums\RsvpStatus;
use App\Models\AnalyticsEvent;
use App\Models\Guest;
use App\Models\Rsvp;
use App\Support\Audit\AuditLogger;
use Illuminate\Validation\ValidationException;

/**
 * Stores the guest's latest response. There is exactly one RSVP row per guest,
 * so a newer submission replaces the previous one; attendance size is clamped
 * against the guest's allowed party size (docs/ROADMAP.md, fase 4).
 */
final class SubmitRsvp
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly TrackEvent $track,
    ) {}

    public function handle(Guest $guest, RsvpStatus $status, int $partySize, ?string $message, bool $showMessage = false): Rsvp
    {
        $message = $message === null ? null : trim($message);
        $message = $message === null || $message === '' ? null : mb_substr($message, 0, 500);

        // A message that is not written cannot be published, so the consent
        // flag is meaningless without one.
        $showMessage = $showMessage && $message !== null;

        if ($status === RsvpStatus::Attending) {
            if ($partySize < 1) {
                throw ValidationException::withMessages([
                    'party_size' => 'Jumlah peserta minimal 1 orang.',
                ]);
            }

            if ($partySize > $guest->allowed_party_size) {
                throw ValidationException::withMessages([
                    'party_size' => "Jumlah peserta melebihi kuota tamu ini ({$guest->allowed_party_size} orang).",
                ]);
            }
        } else {
            $partySize = 0;
        }

        $rsvp = Rsvp::updateOrCreate(
            ['guest_id' => $guest->getKey()],
            [
                'status' => $status,
                'party_size' => $partySize,
                'message' => $message,
                'show_message' => $showMessage,
                'submitted_at' => now(),
            ],
        );

        // The message itself is not copied into the audit trail: it is guest
        // content and may contain personal data (docs/SECURITY.md, section 8).
        $this->audit->log(
            action: 'rsvp.submitted',
            resourceType: 'guest',
            resourceId: $guest->getKey(),
            actor: null,
            metadata: [
                'wedding_id' => $guest->wedding_id,
                'status' => $status->value,
                'party_size' => $partySize,
                'has_message' => $message !== null,
                'message_is_public' => $showMessage,
            ],
        );

        $this->track->handle(AnalyticsEvent::RSVP_SUBMITTED, $guest->wedding, [
            'status' => $status->value,
        ]);

        return $rsvp;
    }
}
