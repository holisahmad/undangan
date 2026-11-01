<?php

declare(strict_types=1);

namespace App\Support\Payments;

/**
 * The manual transfer provider is not a PaymentGateway adapter: there is no
 * remote checkout and no signed webhook. It is identified by this constant so
 * the checkout entry point can branch without hardcoding the string elsewhere.
 */
final class BankTransfer
{
    public const PROVIDER = 'bank_transfer';

    public static function isDefault(): bool
    {
        return (string) config('platform.payments.default_gateway') === self::PROVIDER;
    }

    public static function windowHours(): int
    {
        return max(1, (int) config('platform.payments.bank_transfer.window_hours', 24));
    }

    /**
     * Parses PAYMENT_BANK_ACCOUNTS into a list of destinations. Accepts either
     * "bank|number|holder" entries separated by semicolons or a JSON array.
     *
     * @return list<array{bank: string, number: string, holder: string}>
     */
    public static function accounts(): array
    {
        $raw = config('platform.payments.bank_transfer.accounts', '');

        if (is_array($raw)) {
            $entries = $raw;
        } elseif (is_string($raw) && str_starts_with(ltrim($raw), '[')) {
            $decoded = json_decode($raw, true);
            $entries = is_array($decoded) ? $decoded : [];
        } elseif (is_string($raw)) {
            $entries = array_filter(array_map('trim', explode(';', $raw)));
        } else {
            $entries = [];
        }

        $accounts = [];

        foreach ($entries as $entry) {
            if (is_string($entry)) {
                $parts = array_map('trim', explode('|', $entry));
                $entry = [
                    'bank' => $parts[0] ?? '',
                    'number' => $parts[1] ?? '',
                    'holder' => $parts[2] ?? '',
                ];
            }

            if (! is_array($entry)) {
                continue;
            }

            $bank = trim((string) ($entry['bank'] ?? ''));
            $number = trim((string) ($entry['number'] ?? ''));
            $holder = trim((string) ($entry['holder'] ?? ''));

            if ($bank === '' || $number === '' || $holder === '') {
                continue;
            }

            $accounts[] = ['bank' => $bank, 'number' => $number, 'holder' => $holder];
        }

        return $accounts;
    }

    /**
     * Groups a numeric account number in fours for readability without losing
     * leading zeros.
     */
    public static function prettyNumber(string $number): string
    {
        $digits = preg_replace('/\D+/', '', $number) ?? '';

        if ($digits === '' || strlen($digits) < 8) {
            return $number;
        }

        return trim(implode(' ', str_split($digits, 4)));
    }
}
