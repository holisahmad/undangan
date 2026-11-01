<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Enums\AlbumVisibility;
use App\Models\MediaAlbum;
use App\Models\User;
use App\Models\Wedding;
use App\Support\Audit\AuditLogger;

final class CreateMediaAlbum
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(Wedding $wedding, User $actor, string $title, AlbumVisibility $visibility): MediaAlbum
    {
        $album = MediaAlbum::create([
            'wedding_id' => $wedding->getKey(),
            'title' => trim($title),
            'visibility' => $visibility,
        ]);

        $this->audit->log(
            action: 'media.album.created',
            resourceType: 'media_album',
            resourceId: $album->getKey(),
            actor: $actor,
            metadata: [
                'wedding_id' => $wedding->getKey(),
                'visibility' => $visibility->value,
            ],
        );

        return $album;
    }
}
