<?php

declare(strict_types=1);

namespace App\Support\Security;

use Illuminate\Support\Str;

/**
 * One-time recovery codes for a lost authenticator device. Only SHA-256 hashes
 * are persisted, and a code is removed as soon as it is used.
 */
final class RecoveryCodes
{
    /**
     * @return list<string>
     */
    public static function generate(int $count = 8): array
    {
        $codes = [];

        while (count($codes) < $count) {
            $codes[] = Str::upper(Str::random(5)).'-'.Str::upper(Str::random(5));
        }

        return $codes;
    }

    /**
     * @param  list<string>  $codes
     * @return list<string>
     */
    public static function hashAll(array $codes): array
    {
        return array_map(self::hash(...), $codes);
    }

    public static function hash(string $code): string
    {
        return hash('sha256', Str::upper(trim($code)));
    }

    /**
     * @param  list<string>  $hashes
     * @return array{matched: bool, remaining: list<string>}
     */
    public static function consume(array $hashes, string $code): array
    {
        $candidate = self::hash($code);
        $matched = false;
        $remaining = [];

        foreach ($hashes as $hash) {
            if (! $matched && is_string($hash) && hash_equals($hash, $candidate)) {
                $matched = true;

                continue;
            }

            $remaining[] = (string) $hash;
        }

        return ['matched' => $matched, 'remaining' => $remaining];
    }
}
