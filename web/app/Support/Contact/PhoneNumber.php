<?php

declare(strict_types=1);

namespace App\Support\Contact;

/**
 * Indonesian numbers are written locally ("0813...") but WhatsApp and tel:
 * links need the country code. `preg_replace('/\D+/')` alone produces
 * https://wa.me/081391515363, which WhatsApp rejects as invalid, so every link
 * in the app goes through this class instead.
 */
final class PhoneNumber
{
    /** Digits only, prefixed with the Indonesian country code (62). */
    public static function international(string $number): ?string
    {
        $digits = preg_replace('/\D+/', '', $number) ?? '';

        if ($digits === '') {
            return null;
        }

        $digits = match (true) {
            str_starts_with($digits, '62') => $digits,
            str_starts_with($digits, '0') => '62'.substr($digits, 1),
            str_starts_with($digits, '8') => '62'.$digits,
            default => $digits,
        };

        // 62 plus 8–13 digits is the range Indonesian mobile numbers occupy.
        $length = strlen($digits);

        return $length >= 10 && $length <= 15 ? $digits : null;
    }

    public static function e164(string $number): ?string
    {
        $international = self::international($number);

        return $international === null ? null : '+'.$international;
    }

    public static function whatsappLink(string $number): ?string
    {
        $international = self::international($number);

        return $international === null ? null : 'https://wa.me/'.$international;
    }

    /** 081391515363 becomes 0813-9151-5363, the way the number is written locally. */
    public static function display(string $number): string
    {
        $digits = preg_replace('/\D+/', '', $number) ?? '';

        if (strlen($digits) < 8) {
            return trim($number);
        }

        // Print the local form even when the value is stored internationally.
        if (str_starts_with($digits, '62')) {
            $digits = '0'.substr($digits, 2);
        }

        return substr($digits, 0, 4).'-'.substr($digits, 4, 4).'-'.substr($digits, 8);
    }
}
