<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Album visibility controls the public gallery page only. The owner always
 * sees every album in their own gallery.
 */
enum AlbumVisibility: string
{
    case Private = 'private';
    case Public = 'public';

    public function label(): string
    {
        return match ($this) {
            self::Private => 'Privat',
            self::Public => 'Publik',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
