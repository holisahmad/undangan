<?php

declare(strict_types=1);

namespace App\Actions\Guests;

use App\Models\Guest;
use App\Models\GuestInvitationLink;
use App\Models\User;
use App\Support\Audit\AuditLogger;
use App\Support\Guests\GuestToken;
use Illuminate\Support\Facades\DB;

/**
 * Issues a fresh personal link and revokes any previous one for the guest.
 *
 * The raw token is returned exactly once, to be shown to the owner; only its
 * hash is stored and the token never reaches the audit log
 * (docs/DATABASE_SCHEMA.md, section 6).
 */
final class IssueGuestInvitationLink
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @return array{link: GuestInvitationLink, token: string}
     */
    public function handle(Guest $guest, ?User $actor): array
    {
        $token = GuestToken::generate();

        $link = DB::transaction(function () use ($guest, $token): GuestInvitationLink {
            GuestInvitationLink::query()
                ->where('guest_id', $guest->getKey())
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            return GuestInvitationLink::create([
                'guest_id' => $guest->getKey(),
                'token_hash' => GuestToken::hash($token),
                // The link follows the publication lifetime rather than a
                // fixed timer, so renewing the package keeps it usable.
                'expires_at' => null,
            ]);
        });

        $this->audit->log(
            action: 'guest.link_issued',
            resourceType: 'guest',
            resourceId: $guest->getKey(),
            actor: $actor,
            metadata: [
                'wedding_id' => $guest->wedding_id,
                'link_id' => $link->getKey(),
            ],
        );

        return ['link' => $link, 'token' => $token];
    }
}
