<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Publication state is intentionally separate from payment and entitlement
 * state (docs/PRIVACY_AND_RETENTION.md, section 4).
 */
enum WeddingLifecycleStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Unpublished = 'unpublished';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Published => 'Terbit',
            self::Unpublished => 'Dinonaktifkan',
            self::Expired => 'Berakhir',
        };
    }
}
