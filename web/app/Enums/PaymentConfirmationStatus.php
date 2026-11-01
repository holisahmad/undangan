<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * A manual transfer confirmation moves from submitted to either verified (and
 * the order is settled) or rejected with an operator note.
 */
enum PaymentConfirmationStatus: string
{
    case Submitted = 'submitted';
    case Verified = 'verified';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Menunggu verifikasi',
            self::Verified => 'Terverifikasi',
            self::Rejected => 'Ditolak',
        };
    }

    public function isOpen(): bool
    {
        return $this === self::Submitted;
    }
}
