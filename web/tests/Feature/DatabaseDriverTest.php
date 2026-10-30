<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Guards the PostgreSQL parity job in CI: it fails when the suite is actually
 * running against a different driver than the one the job claims to test.
 */
final class DatabaseDriverTest extends TestCase
{
    public function test_the_active_database_driver_matches_the_expected_driver(): void
    {
        $expected = env('EXPECTED_DB_DRIVER');

        if (blank($expected)) {
            $this->markTestSkipped('EXPECTED_DB_DRIVER is only set by the CI workflow.');
        }

        $this->assertSame($expected, DB::connection()->getDriverName());
    }
}
