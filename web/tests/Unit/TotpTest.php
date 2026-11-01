<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Security\RecoveryCodes;
use App\Support\Security\Totp;
use PHPUnit\Framework\TestCase;

/**
 * The TOTP implementation is checked against the RFC 6238 appendix B vector so
 * the hand-written HMAC/Base32 code cannot silently drift.
 */
final class TotpTest extends TestCase
{
    private const RFC_SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    public function test_it_matches_the_rfc_6238_test_vector(): void
    {
        $this->assertSame('94287082', Totp::code(self::RFC_SECRET, 8, 30, 59));
        $this->assertSame('07081804', Totp::code(self::RFC_SECRET, 8, 30, 1111111109));
        $this->assertSame('89005924', Totp::code(self::RFC_SECRET, 8, 30, 1234567890));
    }

    public function test_it_accepts_one_period_of_clock_drift(): void
    {
        $secret = Totp::generateSecret();
        $code = Totp::code($secret);

        $this->assertTrue(Totp::verify($secret, $code));
        $this->assertTrue(Totp::verify($secret, $code, window: 1, timestamp: time() + 25));
        $this->assertFalse(Totp::verify($secret, $code, window: 0, timestamp: time() + 120));
    }

    public function test_it_rejects_non_numeric_and_missing_codes(): void
    {
        $secret = Totp::generateSecret();

        $this->assertFalse(Totp::verify($secret, 'abcdef'));
        $this->assertFalse(Totp::verify($secret, ''));
        $this->assertFalse(Totp::verify($secret, '1234567'));
    }

    public function test_generated_secrets_are_base32_and_unique(): void
    {
        $first = Totp::generateSecret();
        $second = Totp::generateSecret();

        $this->assertSame(32, strlen($first));
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $first);
        $this->assertNotSame($first, $second);
    }

    public function test_recovery_codes_are_hashed_and_single_use(): void
    {
        $codes = RecoveryCodes::generate(2);
        $hashes = RecoveryCodes::hashAll($codes);

        $this->assertNotContains($codes[0], $hashes);

        $first = RecoveryCodes::consume($hashes, $codes[0]);

        $this->assertTrue($first['matched']);
        $this->assertCount(1, $first['remaining']);

        $second = RecoveryCodes::consume($first['remaining'], $codes[0]);

        $this->assertFalse($second['matched']);
    }
}
