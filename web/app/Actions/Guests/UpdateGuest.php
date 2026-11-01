<?php

declare(strict_types=1);

namespace App\Actions\Guests;

use App\Models\Guest;
use App\Models\User;
use App\Support\Audit\AuditLogger;

final class UpdateGuest
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array{display_name: string, guest_group_id?: string|null, contact_phone?: string|null, contact_email?: string|null, allowed_party_size?: int}  $data
     */
    public function handle(Guest $guest, User $actor, array $data): Guest
    {
        $guest->update([
            'guest_group_id' => $data['guest_group_id'] ?? null,
            'display_name' => trim($data['display_name']),
            'contact_phone' => $data['contact_phone'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'allowed_party_size' => $data['allowed_party_size'] ?? 1,
        ]);

        $this->audit->log(
            action: 'guest.updated',
            resourceType: 'guest',
            resourceId: $guest->getKey(),
            actor: $actor,
            metadata: [
                'wedding_id' => $guest->wedding_id,
                'allowed_party_size' => $guest->allowed_party_size,
            ],
        );

        return $guest;
    }
}
