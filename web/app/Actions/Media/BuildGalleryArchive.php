<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Enums\ArchiveStatus;
use App\Models\ArchiveJob;
use App\Models\MediaAsset;
use App\Models\User;
use App\Models\Wedding;
use App\Support\Audit\AuditLogger;
use App\Support\Media\MediaStorage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Builds a ZIP of every ready asset of one wedding. The work runs inline (no
 * queue worker in this deployment), but the job row keeps the queued,
 * processing, ready, failed and expired states so a failed run stays visible
 * and can be retried (docs/MEDIA_STORAGE.md, sections 5-6).
 */
final class BuildGalleryArchive
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly MediaStorage $storage,
    ) {}

    public function handle(Wedding $wedding, ?User $actor = null): ArchiveJob
    {
        $archive = ArchiveJob::create([
            'wedding_id' => $wedding->getKey(),
            'requested_by_user_id' => $actor?->getKey(),
            'status' => ArchiveStatus::Queued,
        ]);

        return $this->generate($archive, $actor);
    }

    /**
     * @throws ValidationException
     */
    public function retry(ArchiveJob $archive, ?User $actor = null): ArchiveJob
    {
        if (! $archive->status->canRetry()) {
            throw ValidationException::withMessages([
                'archive' => 'Arsip ini masih diproses atau sudah siap diunduh.',
            ]);
        }

        $this->storage->delete($archive->object_key);

        $archive->forceFill([
            'status' => ArchiveStatus::Queued,
            'asset_count' => 0,
            'byte_size' => 0,
            'object_key' => null,
            'manifest_json' => null,
            'error_message' => null,
            'expires_at' => null,
            'started_at' => null,
            'finished_at' => null,
        ])->save();

        return $this->generate($archive, $actor);
    }

    private function generate(ArchiveJob $archive, ?User $actor): ArchiveJob
    {
        $archive->forceFill([
            'status' => ArchiveStatus::Processing,
            'started_at' => now(),
        ])->save();

        $assets = MediaAsset::query()
            ->where('wedding_id', $archive->wedding_id)
            ->ready()
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $maxAssets = (int) config('platform.media.archive_max_assets', 200);
        $maxBytes = (int) config('platform.media.archive_max_bytes', 536_870_912);

        if ($assets->isEmpty()) {
            return $this->fail($archive, $actor, 'Belum ada foto siap diarsipkan.');
        }

        if ($assets->count() > $maxAssets) {
            return $this->fail(
                $archive,
                $actor,
                "Arsip dibatasi {$maxAssets} foto. Hapus sebagian foto atau unduh bertahap.",
            );
        }

        $temp = tempnam(sys_get_temp_dir(), 'undangan-arsip-');

        if ($temp === false) {
            return $this->fail($archive, $actor, 'Tidak dapat membuat berkas sementara.');
        }

        /** @var list<array{asset_id: string, filename: string, byte_size: int, skipped?: bool}> $manifest */
        $manifest = [];
        $totalBytes = 0;

        try {
            $zip = new ZipArchive;

            if ($zip->open($temp, ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Tidak dapat membuat berkas ZIP.');
            }

            foreach ($assets as $index => $asset) {
                if (! $this->storage->exists($asset->object_key)) {
                    // A photo deleted while the job runs must not abort the
                    // archive; the manifest records the skip for auditing.
                    $manifest[] = [
                        'asset_id' => $asset->getKey(),
                        'filename' => $asset->original_filename,
                        'byte_size' => 0,
                        'skipped' => true,
                    ];

                    continue;
                }

                $contents = $this->storage->get($asset->object_key);
                $totalBytes += strlen($contents);

                if ($totalBytes > $maxBytes) {
                    throw new RuntimeException('Ukuran arsip melebihi batas. Unduh bertahap atau hapus sebagian foto.');
                }

                $entry = sprintf('%03d-%s', $index + 1, $this->entryName($asset));

                if (! $zip->addFromString($entry, $contents)) {
                    throw new RuntimeException('Gagal menambahkan berkas ke arsip.');
                }

                $manifest[] = [
                    'asset_id' => $asset->getKey(),
                    'filename' => $entry,
                    'byte_size' => strlen($contents),
                ];
            }

            if (! $zip->close()) {
                throw new RuntimeException('Gagal menutup berkas arsip.');
            }

            $key = 'archives/'.$archive->wedding_id.'/'.$archive->getKey().'.zip';
            $zipBytes = file_get_contents($temp);

            if ($zipBytes === false) {
                throw new RuntimeException('Gagal membaca berkas arsip.');
            }

            $this->storage->put($key, $zipBytes);

            $archive->forceFill([
                'status' => ArchiveStatus::Ready,
                'asset_count' => count($manifest),
                'byte_size' => strlen($zipBytes),
                'object_key' => $key,
                'manifest_json' => $manifest,
                'error_message' => null,
                'expires_at' => now()->addDays((int) config('platform.media.archive_retention_days', 7)),
                'finished_at' => now(),
            ])->save();

            $this->audit->log(
                action: 'media.archive.ready',
                resourceType: 'archive_job',
                resourceId: $archive->getKey(),
                actor: $actor,
                metadata: [
                    'wedding_id' => $archive->wedding_id,
                    'asset_count' => $archive->asset_count,
                    'byte_size' => $archive->byte_size,
                ],
            );

            return $archive;
        } catch (Throwable $exception) {
            return $this->fail($archive, $actor, $exception->getMessage());
        } finally {
            if (file_exists($temp)) {
                @unlink($temp);
            }
        }
    }

    private function fail(ArchiveJob $archive, ?User $actor, string $message): ArchiveJob
    {
        $archive->forceFill([
            'status' => ArchiveStatus::Failed,
            'object_key' => null,
            'error_message' => Str::limit($message, 480, ''),
            'finished_at' => now(),
        ])->save();

        $this->audit->log(
            action: 'media.archive.failed',
            resourceType: 'archive_job',
            resourceId: $archive->getKey(),
            actor: $actor,
            metadata: ['wedding_id' => $archive->wedding_id],
            result: 'failure',
        );

        return $archive;
    }

    private function entryName(MediaAsset $asset): string
    {
        $name = preg_replace('/[^\pL\pN._\- ]+/u', '_', $asset->original_filename) ?? 'foto.jpg';
        $name = trim($name) === '' ? 'foto.jpg' : trim($name);

        return mb_substr($name, 0, 120);
    }
}
