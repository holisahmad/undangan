<?php

declare(strict_types=1);

namespace App\Support\Guests;

use Illuminate\Support\Str;

/**
 * Personal invitation tokens are bearer secrets. The raw value only exists in
 * the URL that is shown once to the owner; storage keeps the SHA-256 hash
 * (docs/DATABASE_SCHEMA.md, section 6).
 */
final class GuestToken
{
    public const LENGTH = 48;

    public static function generate(): string
    {
        return Str::random(self::LENGTH);
    }

    public static function hash(string $rawToken): string
    {
        return hash('sha256', trim($rawToken));
    }
}
