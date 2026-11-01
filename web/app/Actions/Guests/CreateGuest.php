<?php

declare(strict_types=1);

namespace App\Actions\Guests;

use App\Models\Guest;
use App\Models\User;
use App\Models\Wedding;
use App\Support\Audit\AuditLogger;
use App\Support\Guests\GuestQuota;

/**
 * Adds one guest to a wedding. The quota check happens here as well as in the
 * form request, so an import or a future API cannot bypass it.
 */
final class CreateGuest
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly GuestQuota $quota,
    ) {}

    /**
     * @param  array{display_name: string, guest_group_id?: string|null, contact_phone?: string|null, contact_email?: string|null, allowed_party_size?: int}  $data
     */
    public function handle(Wedding $wedding, User $actor, array $data): Guest
    {
        $this->quota->ensureCapacity($wedding, 1);

        $guest = Guest::create([
            'wedding_id' => $wedding->getKey(),
            'guest_group_id' => $data['guest_group_id'] ?? null,
            'display_name' => trim($data['display_name']),
            'contact_phone' => $data['contact_phone'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'allowed_party_size' => $data['allowed_party_size'] ?? 1,
        ]);

        $this->audit->log(
            action: 'guest.created',
            resourceType: 'guest',
            resourceId: $guest->getKey(),
            actor: $actor,
            metadata: [
                'wedding_id' => $wedding->getKey(),
                'allowed_party_size' => $guest->allowed_party_size,
                'has_contact' => $guest->contact_phone !== null || $guest->contact_email !== null,
            ],
        );

        return $guest;
    }
}
