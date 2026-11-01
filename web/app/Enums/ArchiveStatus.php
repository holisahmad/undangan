<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Archive generation is synchronous today, but the states are modelled for a
 * queued worker: a failed archive can be retried without losing its manifest
 * (docs/MEDIA_STORAGE.md, section 5).
 */
enum ArchiveStatus: string
{
    case Queued = 'queued';
    case Processing = 'processing';
    case Ready = 'ready';
    case Failed = 'failed';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Queued => 'Menunggu',
            self::Processing => 'Diproses',
            self::Ready => 'Siap diunduh',
            self::Failed => 'Gagal',
            self::Expired => 'Kedaluwarsa',
        };
    }

    public function canRetry(): bool
    {
        return $this === self::Failed || $this === self::Expired;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
