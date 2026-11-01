<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Models\MediaAlbum;
use App\Models\MediaAsset;
use App\Models\User;
use App\Models\Wedding;
use App\Support\Audit\AuditLogger;
use App\Support\Media\ImageProcessor;
use App\Support\Media\MediaQuota;
use App\Support\Media\MediaStorage;
use App\Support\Media\UploadScanner;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Stores one validated image plus its preview and thumbnail. Originals are
 * written before the row exists so a failed derivative never leaves a database
 * row pointing at nothing.
 */
final class UploadMediaAsset
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly MediaQuota $quota,
        private readonly MediaStorage $storage,
        private readonly ImageProcessor $images,
        private readonly UploadScanner $scanner,
    ) {}

    /**
     * @throws ValidationException
     */
    public function handle(Wedding $wedding, User $actor, UploadedFile $file, ?MediaAlbum $album = null): MediaAsset
    {
        $path = $file->getRealPath();

        if ($path === false) {
            throw ValidationException::withMessages(['photos' => 'Berkas tidak dapat dibaca dari unggahan.']);
        }

        $meta = $this->images->inspect($path);
        $this->scanner->scan($path);
        $this->quota->ensureCapacity($wedding, 1, (int) $file->getSize());

        $derivatives = $this->images->derivatives($path);

        $base = 'weddings/'.$wedding->getKey().'/'.Str::uuid();
        $objectKey = $base.'.'.$meta['extension'];
        $previewKey = null;
        $thumbnailKey = null;

        if ($derivatives !== null) {
            $previewKey = $base.'-preview.'.$derivatives['preview']['extension'];
            $thumbnailKey = $base.'-thumb.'.$derivatives['thumbnail']['extension'];
        }

        $original = file_get_contents($path);

        if ($original === false) {
            throw ValidationException::withMessages(['photos' => 'Berkas tidak dapat dibaca dari unggahan.']);
        }

        $this->storage->put($objectKey, $original);

        if ($derivatives !== null) {
            $this->storage->put((string) $previewKey, $derivatives['preview']['bytes']);
            $this->storage->put((string) $thumbnailKey, $derivatives['thumbnail']['bytes']);
        }

        $asset = MediaAsset::create([
            'wedding_id' => $wedding->getKey(),
            'album_id' => $album?->getKey(),
            'uploaded_by_user_id' => $actor->getKey(),
            'object_key' => $objectKey,
            'preview_object_key' => $previewKey,
            'thumbnail_object_key' => $thumbnailKey,
            'original_filename' => $this->displayName($file->getClientOriginalName()),
            'mime_type' => $meta['mime'],
            'checksum_sha256' => $meta['checksum'],
            'byte_size' => strlen($original),
            'width' => $meta['width'],
            'height' => $meta['height'],
            'status' => 'ready',
        ]);

        $this->audit->log(
            action: 'media.uploaded',
            resourceType: 'media_asset',
            resourceId: $asset->getKey(),
            actor: $actor,
            metadata: [
                'wedding_id' => $wedding->getKey(),
                'byte_size' => $asset->byte_size,
                'album_id' => $album?->getKey(),
            ],
        );

        return $asset;
    }

    private function displayName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $sanitised = preg_replace('/[^\pL\pN._\- ]+/u', '_', $name) ?? 'foto';

        return mb_substr(trim($sanitised) === '' ? 'foto' : trim($sanitised), 0, 250);
    }
}
