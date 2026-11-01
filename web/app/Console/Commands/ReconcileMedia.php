<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ArchiveStatus;
use App\Enums\MediaStatus;
use App\Models\ArchiveJob;
use App\Models\MediaAsset;
use App\Support\Media\MediaStorage;
use Illuminate\Console\Command;

/**
 * Compares object storage with metadata so drift is visible before it becomes
 * a customer complaint. Orphans are reported, never deleted automatically:
 * they need a quarantine window and a manual decision
 * (docs/MEDIA_STORAGE.md, section 8).
 */
final class ReconcileMedia extends Command
{
    protected $signature = 'media:reconcile {--strict : Exit with a failure status when drift is found}';

    protected $description = 'Report media objects missing from storage and orphan objects on disk';

    public function handle(MediaStorage $storage): int
    {
        $assets = MediaAsset::query()
            ->where('status', '!=', MediaStatus::Deleted)
            ->get(['object_key', 'preview_object_key', 'thumbnail_object_key']);

        $missing = [];
        $known = [];

        foreach ($assets as $asset) {
            foreach ([$asset->object_key, $asset->preview_object_key, $asset->thumbnail_object_key] as $key) {
                if ($key === null) {
                    continue;
                }

                $known[$key] = true;

                if (! $storage->exists($key)) {
                    $missing[] = $key;
                }
            }
        }

        $orphans = array_values(array_filter(
            $storage->filesUnder('weddings'),
            static fn (string $key): bool => ! isset($known[$key]),
        ));

        $archiveRows = ArchiveJob::query()
            ->where('status', ArchiveStatus::Ready)
            ->whereNotNull('object_key')
            ->get(['object_key']);

        $missingArchives = $archiveRows
            ->filter(static fn (ArchiveJob $archive): bool => ! $storage->exists((string) $archive->object_key))
            ->map(static fn (ArchiveJob $archive): string => (string) $archive->object_key)
            ->values()
            ->all();

        $this->line('Objek tercatat: '.count($known));
        $this->line('File hilang: '.count($missing));
        $this->line('Objek yatim: '.count($orphans));
        $this->line('Arsip tanpa file: '.count($missingArchives));

        foreach (array_slice($missing, 0, 20) as $key) {
            $this->warn("Hilang: {$key}");
        }

        foreach (array_slice($orphans, 0, 20) as $key) {
            $this->warn("Yatim: {$key}");
        }

        foreach (array_slice($missingArchives, 0, 20) as $key) {
            $this->warn("Arsip hilang: {$key}");
        }

        $drift = $missing !== [] || $orphans !== [] || $missingArchives !== [];

        if ($drift && $this->option('strict')) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
