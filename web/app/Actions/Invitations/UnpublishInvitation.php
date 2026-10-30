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

    public function handle(Wedding $wedding, User $actor): void
    {
        DB::transaction(function () use ($wedding, $actor): void {
            $now = now();

            $affected = $wedding->publications()
                ->whereNull('unpublished_at')
                ->update(['unpublished_at' => $now, 'updated_at' => $now]);

            $wedding->update(['lifecycle_status' => WeddingLifecycleStatus::Unpublished]);

            $this->audit->log(
                action: 'invitation.unpublished',
                resourceType: 'wedding',
                resourceId: $wedding->getKey(),
                actor: $actor,
                metadata: ['publications_disabled' => $affected],
            );
        });
    }
}
