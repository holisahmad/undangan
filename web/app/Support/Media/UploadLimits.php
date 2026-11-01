<?php

declare(strict_types=1);

namespace App\Support\Media;

/**
 * Effective upload ceilings for one request.
 *
 * config/platform.php states the product limits, but PHP rejects a request
 * before Laravel sees it when the body exceeds post_max_size, and drops a single
 * file larger than upload_max_filesize. Both values change per host, so the
 * numbers shown on the gallery page and the numbers used by validation are the
 * lower of the two sources (docs/MEDIA_STORAGE.md, section 10).
 */
final class UploadLimits
{
    /**
     * Multipart boundaries, the CSRF field and the other form fields share the
     * request budget, so the ceiling keeps a margin below post_max_size instead
     * of colliding with it.
     */
    private const REQUEST_RESERVE_KB = 512;

    public function __construct(
        private readonly int $configuredFileKb,
        private readonly int $configuredRequestKb,
        private readonly int $maxFiles,
        private readonly ?int $phpFileKb = null,
        private readonly ?int $phpRequestKb = null,
    ) {}

    public static function current(): self
    {
        $fileKb = max(1, (int) config('platform.media.max_file_kb', 8192));
        $requestKb = max(1, (int) config('platform.media.max_upload_kb', 24576));
        $maxFiles = max(1, (int) config('platform.media.max_files_per_upload', 10));

        if (! (bool) config('platform.media.respect_php_limits', true)) {
            return new self($fileKb, $requestKb, $maxFiles);
        }

        return new self(
            $fileKb,
            $requestKb,
            $maxFiles,
            self::toKb((string) ini_get('upload_max_filesize')),
            self::toKb((string) ini_get('post_max_size')),
        );
    }

    public function fileKb(): int
    {
        $fileKb = $this->phpFileKb === null ? $this->configuredFileKb : min($this->configuredFileKb, $this->phpFileKb);

        // One file can never be larger than the whole request it travels in.
        return max(1, min($fileKb, $this->requestKb()));
    }

    public function fileBytes(): int
    {
        return $this->fileKb() * 1024;
    }

    public function requestKb(): int
    {
        $requestKb = $this->phpRequestKb === null
            ? $this->configuredRequestKb
            : min($this->configuredRequestKb, max(1, $this->phpRequestKb - self::REQUEST_RESERVE_KB));

        return max(1, $requestKb);
    }

    public function requestBytes(): int
    {
        return $this->requestKb() * 1024;
    }

    public function maxFiles(): int
    {
        return $this->maxFiles;
    }

    public function fileMb(): int
    {
        return max(1, (int) ceil($this->fileKb() / 1024));
    }

    public function requestMb(): string
    {
        return self::megabytes($this->requestBytes());
    }

    /**
     * True when PHP's configuration, not the product config, decides a ceiling.
     * The gallery page says so plainly instead of advertising a size the server
     * will refuse.
     */
    public function clampedByPhp(): bool
    {
        return $this->fileKb() < $this->configuredFileKb || $this->requestKb() < $this->configuredRequestKb;
    }

    public function configuredFileMb(): int
    {
        return max(1, (int) ceil($this->configuredFileKb / 1024));
    }

    /**
     * Formats bytes for Indonesian copy ("7,5").
     */
    public static function megabytes(int $bytes): string
    {
        return number_format($bytes / 1_048_576, 1, ',', '.');
    }

    /**
     * Parses a php.ini shorthand ("2M", "512K", "1G", "8388608"). Zero and
     * negative values mean "unlimited" in php.ini, so they yield null.
     */
    public static function toKb(string $value): ?int
    {
        if (preg_match('/^\s*(-?[0-9.]+)\s*([kmg]?)\s*$/i', $value, $matches) !== 1) {
            return null;
        }

        $number = (float) $matches[1];

        if ($number <= 0) {
            return null;
        }

        return match (strtolower($matches[2])) {
            'g' => (int) round($number * 1_048_576),
            'm' => (int) round($number * 1024),
            'k' => (int) round($number),
            default => (int) round($number / 1024),
        };
    }
}
