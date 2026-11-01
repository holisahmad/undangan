<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Guest response to an invitation. One row per guest, so a newer response
 * always replaces the previous one (docs/adr/008-guests-and-rsvp.md).
 */
enum RsvpStatus: string
{
    case Attending = 'attending';
    case Declined = 'declined';
    case Maybe = 'maybe';

    public function label(): string
    {
        return match ($this) {
            self::Attending => 'Hadir',
            self::Declined => 'Tidak hadir',
            self::Maybe => 'Masih ragu',
        };
    }

    /**
     * Accepted form values, used by validation and tests.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
