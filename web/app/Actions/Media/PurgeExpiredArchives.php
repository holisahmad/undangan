<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Enums\ArchiveStatus;
use App\Models\ArchiveJob;
use App\Support\Media\MediaStorage;
use DateTimeInterface;

/**
 * Removes archive files whose retention window has passed. Only the file is
 * purged; the row keeps the manifest so support can explain what was inside
 * (docs/MEDIA_STORAGE.md, section 6).
 */
final class PurgeExpiredArchives
{
    public function __construct(private readonly MediaStorage $storage) {}

    public function handle(?DateTimeInterface $moment = null): int
    {
        $moment ??= now();

        $archives = ArchiveJob::query()
            ->where('status', ArchiveStatus::Ready)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $moment)
            ->get();

        foreach ($archives as $archive) {
            $this->storage->delete($archive->object_key);

            $archive->forceFill([
                'status' => ArchiveStatus::Expired,
                'object_key' => null,
            ])->save();
        }

        return $archives->count();
    }
}
