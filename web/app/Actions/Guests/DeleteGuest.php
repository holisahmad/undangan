<?php

declare(strict_types=1);

namespace App\Actions\Guests;

use App\Models\Guest;
use App\Models\User;
use App\Support\Audit\AuditLogger;

/**
 * Deleting a guest cascades to the RSVP row and every personal link, so a
 * deleted guest can no longer open a personalised invitation.
 */
final class DeleteGuest
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(Guest $guest, User $actor): void
    {
        $weddingId = $guest->wedding_id;
        $guestId = $guest->getKey();

        $guest->delete();

        $this->audit->log(
            action: 'guest.deleted',
            resourceType: 'guest',
            resourceId: $guestId,
            actor: $actor,
            metadata: ['wedding_id' => $weddingId],
        );
    }
}
