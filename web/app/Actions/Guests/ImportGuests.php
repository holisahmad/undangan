<?php

declare(strict_types=1);

namespace App\Actions\Guests;

use App\Models\Guest;
use App\Models\GuestGroup;
use App\Models\User;
use App\Models\Wedding;
use App\Support\Audit\AuditLogger;
use App\Support\Guests\GuestQuota;
use Illuminate\Support\Facades\DB;

/**
 * Import is all-or-nothing: the parser reports every problematic row first, and
 * this action only runs when the whole file is valid.
 */
final class ImportGuests
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly GuestQuota $quota,
    ) {}

    /**
     * @param  list<array{display_name: string, group: string|null, phone: string|null, email: string|null, allowed_party_size: int}>  $rows
     * @return int Number of imported guests.
     */
    public function handle(Wedding $wedding, array $rows, User $actor): int
    {
        if ($rows === []) {
            return 0;
        }

        $this->quota->ensureCapacity($wedding, count($rows));

        $imported = DB::transaction(function () use ($wedding, $rows): int {
            /** @var array<string, string> $groups */
            $groups = [];

            foreach ($rows as $row) {
                $groupId = null;

                if ($row['group'] !== null) {
                    $groupId = $groups[$row['group']] ??= GuestGroup::query()
                        ->firstOrCreate(['wedding_id' => $wedding->getKey(), 'name' => $row['group']])
                        ->getKey();
                }

                Guest::create([
                    'wedding_id' => $wedding->getKey(),
                    'guest_group_id' => $groupId,
                    'display_name' => $row['display_name'],
                    'contact_phone' => $row['phone'],
                    'contact_email' => $row['email'],
                    'allowed_party_size' => $row['allowed_party_size'],
                ]);
            }

            return count($rows);
        });

        $this->audit->log(
            action: 'guest.imported',
            resourceType: 'wedding',
            resourceId: $wedding->getKey(),
            actor: $actor,
            metadata: ['count' => $imported],
        );

        return $imported;
    }
}
