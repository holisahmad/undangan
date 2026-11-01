<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Enums\MediaStatus;
use App\Models\MediaAsset;
use App\Models\User;
use App\Support\Audit\AuditLogger;
use App\Support\Media\MediaStorage;
use Throwable;

/**
 * Deletes an asset's objects before marking the row deleted. If storage fails
 * the row stays in deletion_pending so the cleanup command can retry instead
 * of leaving an orphan object behind.
 */
final class DeleteMediaAsset
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly MediaStorage $storage,
    ) {}

    public function handle(MediaAsset $asset, ?User $actor = null): void
    {
        $asset->forceFill(['status' => MediaStatus::DeletionPending])->save();

        try {
            $this->storage->delete($asset->object_key);
            $this->storage->delete($asset->preview_object_key);
            $this->storage->delete($asset->thumbnail_object_key);
        } catch (Throwable $exception) {
            $this->audit->log(
                action: 'media.delete_failed',
                resourceType: 'media_asset',
                resourceId: $asset->getKey(),
                actor: $actor,
                metadata: ['wedding_id' => $asset->wedding_id],
                result: 'failure',
            );

            throw $exception;
        }

        $asset->forceFill([
            'status' => MediaStatus::Deleted,
            'deleted_at' => now(),
        ])->save();

        $this->audit->log(
            action: 'media.deleted',
            resourceType: 'media_asset',
            resourceId: $asset->getKey(),
            actor: $actor,
            metadata: [
                'wedding_id' => $asset->wedding_id,
                'byte_size' => $asset->byte_size,
            ],
        );
    }
}
