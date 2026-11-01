<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Media\DeleteMediaAsset;
use App\Actions\Media\PurgeExpiredArchives;
use App\Enums\ArchiveStatus;
use App\Enums\MediaStatus;
use App\Models\ArchiveJob;
use App\Models\MediaAsset;
use Illuminate\Console\Command;
use Throwable;

/**
 * Scheduled maintenance for gallery retention. Expired archives are purged and
 * deletions that previously failed on storage are retried after a quarantine
 * window (docs/MEDIA_STORAGE.md, sections 6-7).
 */
final class CleanupMedia extends Command
{
    protected $signature = 'media:cleanup {--dry-run : Report what would be removed without touching storage}';

    protected $description = 'Purge expired gallery archives and retry pending media deletions';

    public function handle(PurgeExpiredArchives $purge, DeleteMediaAsset $delete): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $quarantineHours = (int) config('platform.media.cleanup_quarantine_hours', 24);

        $archives = ArchiveJob::query()
            ->where('status', ArchiveStatus::Ready)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->count();

        $pendingDeletions = MediaAsset::query()
            ->where('status', MediaStatus::DeletionPending)
            ->where('updated_at', '<=', now()->subHours($quarantineHours))
            ->count();

        if ($dryRun) {
            $this->info("Arsip kedaluwarsa: {$archives}. Penghapusan tertunda: {$pendingDeletions}. (dry-run)");

            return self::SUCCESS;
        }

        $purged = $purge->handle();

        $retried = 0;
        $failed = 0;

        $assets = MediaAsset::query()
            ->where('status', MediaStatus::DeletionPending)
            ->where('updated_at', '<=', now()->subHours($quarantineHours))
            ->get();

        foreach ($assets as $asset) {
            try {
                $delete->handle($asset);
                $retried++;
            } catch (Throwable $exception) {
                $failed++;
                $this->warn("Gagal menghapus media {$asset->getKey()}: {$exception->getMessage()}");
            }
        }

        $this->info("Arsip kedaluwarsa dibersihkan: {$purged}. Penghapusan dicoba ulang: {$retried}. Gagal: {$failed}.");

        return self::SUCCESS;
    }
}
