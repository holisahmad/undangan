<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Operational seams that only matter once the app is scheduled and monitored:
 * retention has to run without a human, and the health endpoint has to answer.
 */
final class OperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_retention_commands_are_scheduled(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('media:cleanup')
            ->expectsOutputToContain('media:reconcile')
            ->assertExitCode(0);
    }

    public function test_the_health_endpoint_reports_ok(): void
    {
        $this->get('/up')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }
}
