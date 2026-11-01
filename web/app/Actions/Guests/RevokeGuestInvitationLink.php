<?php

declare(strict_types=1);

namespace App\Actions\Guests;

use App\Models\Guest;
use App\Models\GuestInvitationLink;
use App\Models\User;
use App\Support\Audit\AuditLogger;

final class RevokeGuestInvitationLink
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(Guest $guest, User $actor): int
    {
        $revoked = GuestInvitationLink::query()
            ->where('guest_id', $guest->getKey())
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        if ($revoked === 0) {
            return 0;
        }

        $this->audit->log(
            action: 'guest.link_revoked',
            resourceType: 'guest',
            resourceId: $guest->getKey(),
            actor: $actor,
            metadata: [
                'wedding_id' => $guest->wedding_id,
                'revoked_count' => $revoked,
            ],
        );

        return $revoked;
    }
}
