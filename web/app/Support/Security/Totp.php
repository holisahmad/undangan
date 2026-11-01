<?php

declare(strict_types=1);

namespace App\Support\Security;

/**
 * RFC 6238 time-based one-time passwords without an extra dependency.
 *
 * The repository ships no 2FA package and composer is restricted in this
 * environment, so the algorithm is implemented against the published test
 * vector instead (see SecurityHardeningTest).
 */
final class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes($bytes));
    }

    public static function code(string $secret, int $digits = 6, int $period = 30, ?int $timestamp = null): string
    {
        $counter = intdiv($timestamp ?? time(), $period);

        // 8-byte big-endian counter, as defined by RFC 4226 section 5.2.
        $binary = pack('N*', 0).pack('N', $counter);
        $hash = hash_hmac('sha1', $binary, self::base32Decode($secret), true);

        $offset = ord($hash[19]) & 0x0F;
        $value = ((ord($hash[$offset]) & 0x7F) << 24)
            | (ord($hash[$offset + 1]) << 16)
            | (ord($hash[$offset + 2]) << 8)
            | ord($hash[$offset + 3]);

        return str_pad((string) ($value % (10 ** $digits)), $digits, '0', STR_PAD_LEFT);
    }

    /**
     * Accepts one period of clock drift in either direction, which is the
     * tolerance authenticator apps assume.
     */
    public static function verify(
        string $secret,
        string $code,
        int $window = 1,
        int $digits = 6,
        int $period = 30,
        ?int $timestamp = null,
    ): bool {
        $code = preg_replace('/\s+/', '', $code) ?? '';

        if (preg_match('/^\d{'.$digits.'}$/', $code) !== 1) {
            return false;
        }

        $now = $timestamp ?? time();

        for ($offset = -$window; $offset <= $window; $offset++) {
            $candidate = self::code($secret, $digits, $period, $now + ($offset * $period));

            if (hash_equals($candidate, $code)) {
                return true;
            }
        }

        return false;
    }

    public static function provisioningUri(string $secret, string $account, string $issuer): string
    {
        return 'otpauth://totp/'.rawurlencode($issuer.':'.$account).'?'.http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => 6,
            'period' => 30,
        ]);
    }

    private static function base32Encode(string $binary): string
    {
        $bits = '';

        foreach (str_split($binary) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        $secret = '';

        foreach (str_split($bits, 5) as $chunk) {
            $secret .= self::ALPHABET[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
        }

        return $secret;
    }

    private static function base32Decode(string $secret): string
    {
        $bits = '';

        foreach (str_split(strtoupper(rtrim($secret, '='))) as $character) {
            $position = strpos(self::ALPHABET, $character);

            if ($position === false) {
                continue;
            }

            $bits .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
        }

        $binary = '';

        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $binary .= chr(bindec($chunk));
            }
        }

        return $binary;
    }
}
