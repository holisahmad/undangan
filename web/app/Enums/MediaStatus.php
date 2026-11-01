<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Lifecycle of a stored media asset. Only `ready` assets may be rendered on a
 * page or put into an archive (docs/PRIVACY_AND_RETENTION.md, section 4).
 */
enum MediaStatus: string
{
    case PendingUpload = 'pending_upload';
    case Uploaded = 'uploaded';
    case Processing = 'processing';
    case Ready = 'ready';
    case Quarantined = 'quarantined';
    case DeletionPending = 'deletion_pending';
    case Deleted = 'deleted';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::PendingUpload => 'Menunggu unggahan',
            self::Uploaded => 'Terunggah',
            self::Processing => 'Diproses',
            self::Ready => 'Siap',
            self::Quarantined => 'Karantina',
            self::DeletionPending => 'Menunggu dihapus',
            self::Deleted => 'Dihapus',
            self::Failed => 'Gagal',
        };
    }

    public function isPubliclyVisible(): bool
    {
        return $this === self::Ready;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
