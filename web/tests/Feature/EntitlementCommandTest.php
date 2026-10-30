<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EntitlementCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_trial_entitlement_can_be_granted_by_slug(): void
    {
        $wedding = Wedding::factory()->create();

        $this->artisan('entitlement:grant-trial', [
            'wedding' => $wedding->slug,
            '--months' => 3,
        ])->assertSuccessful();

        $this->assertDatabaseHas('service_entitlements', [
            'wedding_id' => $wedding->getKey(),
            'entitlement_type' => 'invitation',
            'status' => 'active',
        ]);

        $this->assertNotNull($wedding->activeInvitationEntitlement());

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'entitlement.granted',
            'resource_id' => $wedding->getKey(),
        ]);
    }

    public function test_an_unknown_wedding_fails_without_writing_anything(): void
    {
        $this->artisan('entitlement:grant-trial', ['wedding' => 'tidak-ada'])
            ->assertFailed();

        $this->assertDatabaseCount('service_entitlements', 0);
    }
}
