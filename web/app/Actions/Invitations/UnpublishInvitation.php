<?php

declare(strict_types=1);

namespace App\Actions\Invitations;

use App\Enums\WeddingLifecycleStatus;
use App\Models\User;
use App\Models\Wedding;
use App\Support\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

final class UnpublishInvitation
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  string|null  $reason  Set only when an operator suspends the
     *                               publication on the owner's behalf; it moves
     *                               the audit action to the admin namespace.
     */
    public function handle(Wedding $wedding, User $actor, ?string $reason = null): void
    {
        DB::transaction(function () use ($wedding, $actor, $reason): void {
            $now = now();

            $affected = $wedding->publications()
                ->whereNull('unpublished_at')
                ->update(['unpublished_at' => $now, 'updated_at' => $now]);

            $wedding->update(['lifecycle_status' => WeddingLifecycleStatus::Unpublished]);

            $metadata = ['publications_disabled' => $affected];

            if ($reason !== null) {
                $metadata['reason'] = $reason;
            }

            $this->audit->log(
                action: $reason === null ? 'invitation.unpublished' : 'admin.invitation.suspended',
                resourceType: 'wedding',
                resourceId: $wedding->getKey(),
                actor: $actor,
                metadata: $metadata,
            );
        });
    }
}
