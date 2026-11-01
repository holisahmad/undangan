<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ArchiveJob;
use App\Models\MediaAsset;
use App\Support\Media\MediaStorage;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves private objects through short-lived signed URLs. No other route
 * returns media bytes, and the disk itself is not publicly served
 * (docs/MEDIA_STORAGE.md, section 5).
 */
final class MediaDownloadController extends Controller
{
    public function asset(Request $request, MediaAsset $mediaAsset, MediaStorage $storage): StreamedResponse
    {
        if (! $mediaAsset->isReady()) {
            abort(404);
        }

        $variant = (string) $request->query('variant', 'original');

        [$key, $mime, $filename] = match ($variant) {
            'thumb' => [$mediaAsset->thumbnail_object_key, 'image/jpeg', $this->variantName($mediaAsset, 'thumb', $mediaAsset->thumbnail_object_key)],
            'preview' => [$mediaAsset->preview_object_key, 'image/jpeg', $this->variantName($mediaAsset, 'preview', $mediaAsset->preview_object_key)],
            default => [$mediaAsset->object_key, $mediaAsset->mime_type, $mediaAsset->original_filename],
        };

        // A build without a JPEG/WebP codec stores no renders; serving the
        // original keeps the gallery usable instead of showing broken images.
        if ($key === null) {
            $variant = 'original';
            [$key, $mime, $filename] = [$mediaAsset->object_key, $mediaAsset->mime_type, $mediaAsset->original_filename];
        }

        $mime = $variant === 'original'
            ? $mime
            : (str_ends_with((string) $key, '.png') ? 'image/png' : 'image/jpeg');

        if ($key === null || ! $storage->exists($key)) {
            abort(404);
        }

        $stream = $storage->disk()->readStream($key);

        if (! is_resource($stream)) {
            abort(404);
        }

        $callback = static function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        };

        if ($variant === 'original') {
            return response()->streamDownload($callback, $filename, ['Content-Type' => $mime]);
        }

        // Derivatives are content-addressed by object key and never rewritten in
        // place, so the browser may keep them for a year (docs/MEDIA_STORAGE.md).
        return response()->stream($callback, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=31536000, immutable',
        ]);
    }

    public function archive(ArchiveJob $archive, MediaStorage $storage): StreamedResponse
    {
        if (! $archive->isDownloadable()) {
            abort(410);
        }

        $key = (string) $archive->object_key;

        if (! $storage->exists($key)) {
            abort(410);
        }

        $stream = $storage->disk()->readStream($key);

        if (! is_resource($stream)) {
            abort(410);
        }

        $filename = 'galeri-'.($archive->created_at?->format('Ymd-His') ?? $archive->getKey()).'.zip';

        return response()->streamDownload(static function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        }, $filename, ['Content-Type' => 'application/zip']);
    }

    private function variantName(MediaAsset $asset, string $variant, ?string $key): string
    {
        $base = pathinfo($asset->original_filename, PATHINFO_FILENAME);
        $extension = $key !== null ? pathinfo($key, PATHINFO_EXTENSION) : 'jpg';

        return $base.'-'.$variant.'.'.$extension;
    }
}
