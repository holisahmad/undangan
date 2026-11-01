<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Models\MediaAlbum;
use App\Models\User;
use App\Support\Audit\AuditLogger;

/**
 * Deleting an album detaches its photos (nullOnDelete) instead of destroying
 * them: the owner chose to remove a grouping, not the pictures.
 */
final class DeleteMediaAlbum
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(MediaAlbum $album, User $actor): void
    {
        $this->audit->log(
            action: 'media.album.deleted',
            resourceType: 'media_album',
            resourceId: $album->getKey(),
            actor: $actor,
            metadata: ['wedding_id' => $album->wedding_id],
        );

        $album->delete();
    }
}
