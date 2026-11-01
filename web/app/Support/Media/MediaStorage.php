<?php

declare(strict_types=1);

namespace App\Support\Media;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * Single entry point for object storage access. The disk name comes from
 * configuration so a deployment can swap the local adapter for S3-compatible
 * storage without touching call sites (docs/adr/003-media-storage.md).
 */
final class MediaStorage
{
    public function disk(): Filesystem
    {
        return Storage::disk($this->diskName());
    }

    public function diskName(): string
    {
        return (string) config('platform.media.disk', 'media');
    }

    public function put(string $path, string $contents): void
    {
        $this->disk()->put($path, $contents);
    }

    public function exists(string $path): bool
    {
        return $this->disk()->exists($path);
    }

    public function get(string $path): string
    {
        return (string) $this->disk()->get($path);
    }

    public function delete(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        if ($this->exists($path)) {
            $this->disk()->delete($path);
        }
    }

    /**
     * @return list<string>
     */
    public function filesUnder(string $directory): array
    {
        /** @var list<string> $files */
        $files = array_values($this->disk()->allFiles($directory));

        return $files;
    }
}
