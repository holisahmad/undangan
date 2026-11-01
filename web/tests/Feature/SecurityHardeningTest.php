<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MediaAsset;
use App\Models\User;
use App\Models\Wedding;
use App\Support\Media\UploadScanner;
use App\Support\Security\RecoveryCodes;
use App\Support\Security\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Hardening seams from the Sprint 5 audit: operator 2FA, CSP in report-only
 * mode, registration throttling, immutable derivative caching and the upload
 * scanner hook (docs/SECURITY.md, sections 8-9).
 */
final class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('media');
    }

    public function test_an_operator_with_two_factor_must_pass_the_challenge_before_a_session_starts(): void
    {
        $secret = Totp::generateSecret();
        $admin = $this->operator($secret);

        $this->post(route('login'), ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('two-factor.login'));

        $this->assertGuest();

        $this->get(route('two-factor.login'))->assertOk();

        $this->post(route('two-factor.login'), ['code' => 'abcdef'])
            ->assertSessionHasErrors('code');

        $this->assertGuest();

        $this->post(route('two-factor.login'), ['code' => Totp::code($secret)])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($admin);
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.two_factor_passed']);
    }

    public function test_a_recovery_code_can_be_used_once_and_is_then_removed(): void
    {
        $secret = Totp::generateSecret();
        $codes = RecoveryCodes::generate(3);
        $admin = $this->operator($secret);
        $admin->forceFill(['two_factor_recovery_codes' => RecoveryCodes::hashAll($codes)])->save();

        $this->post(route('login'), ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('two-factor.login'));

        $this->post(route('two-factor.login'), ['recovery_code' => $codes[0]])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($admin);
        $this->assertCount(2, $admin->refresh()->twoFactorRecoveryHashes());
    }

    public function test_an_operator_enrols_and_confirms_an_authenticator(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('security.edit'))
            ->assertOk()
            ->assertSee('Kunci manual');

        $secret = (string) $this->app['session.store']->get('two_factor.pending_secret');
        $this->assertNotSame('', $secret);

        $this->actingAs($admin)
            ->post(route('security.confirm'), ['code' => Totp::code($secret)])
            ->assertRedirect(route('security.edit'));

        $admin->refresh();

        $this->assertTrue($admin->hasTwoFactorEnabled());
        // The cast encrypts the column, so the plaintext secret is never stored.
        $this->assertDatabaseMissing('users', ['id' => $admin->getKey(), 'two_factor_secret' => $secret]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.two_factor_enabled']);
    }

    public function test_disabling_two_factor_requires_the_password_and_a_valid_code(): void
    {
        $secret = Totp::generateSecret();
        $admin = $this->operator($secret);

        $this->actingAs($admin)
            ->delete(route('security.destroy'), ['password' => 'wrong-password', 'code' => Totp::code($secret)])
            ->assertSessionHasErrors('password');

        $this->assertTrue($admin->refresh()->hasTwoFactorEnabled());

        $this->actingAs($admin)
            ->delete(route('security.destroy'), ['password' => 'password', 'code' => 'abcdef'])
            ->assertSessionHasErrors('code');

        $this->assertTrue($admin->refresh()->hasTwoFactorEnabled());

        $this->actingAs($admin)
            ->delete(route('security.destroy'), ['password' => 'password', 'code' => Totp::code($secret)])
            ->assertRedirect(route('security.edit'));

        $this->assertFalse($admin->refresh()->hasTwoFactorEnabled());
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.two_factor_disabled']);
    }

    public function test_requiring_two_factor_locks_an_unenrolled_operator_out_of_admin(): void
    {
        config(['platform.security.require_admin_two_factor' => true]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.weddings.index'))
            ->assertRedirect(route('security.edit'));

        $this->actingAs($admin)->get(route('security.edit'))->assertOk();

        $admin->forceFill([
            'two_factor_secret' => Totp::generateSecret(),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $this->actingAs($admin)->get(route('admin.weddings.index'))->assertOk();
    }

    public function test_every_response_advertises_a_report_only_csp_and_accepts_reports(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertHeader('Content-Security-Policy-Report-Only')
            ->assertHeader('Reporting-Endpoints', 'csp="/csp-reports"');

        $this->postJson(route('security.csp-report'), [
            'csp-report' => [
                'document-uri' => 'https://undangan.test/undangan/contoh',
                'violated-directive' => 'style-src-elem',
                'blocked-uri' => 'inline',
            ],
        ])->assertNoContent();
    }

    /**
     * Switching the report-only flag off is the documented hardening step, so it
     * must enforce the policy instead of dropping the header altogether.
     */
    public function test_the_policy_is_enforced_once_report_only_is_switched_off(): void
    {
        config(['platform.security.csp_report_only' => false]);

        $this->get('/')
            ->assertOk()
            ->assertHeader('Content-Security-Policy', (string) config('platform.security.csp_policy'))
            ->assertHeaderMissing('Content-Security-Policy-Report-Only')
            ->assertHeaderMissing('Reporting-Endpoints');
    }

    public function test_registration_is_rate_limited_per_ip(): void
    {
        User::factory()->create(['email' => 'taken@example.test']);

        $payload = [
            'name' => 'Ayu Lestari',
            'email' => 'taken@example.test',
            'password' => 'rahasia-123',
            'password_confirmation' => 'rahasia-123',
        ];

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('register'), $payload)->assertSessionHasErrors('email');
        }

        $this->post(route('register'), $payload)->assertStatus(429);
    }

    public function test_derivatives_are_served_with_an_immutable_private_cache(): void
    {
        [$owner, $wedding] = $this->ownerWedding();
        $asset = MediaAsset::factory()->for($wedding)->create();
        Storage::disk('media')->put((string) $asset->thumbnail_object_key, 'binary');

        $url = URL::temporarySignedRoute('media.photos.download', now()->addMinutes(5), [
            'mediaAsset' => $asset->getKey(),
            'variant' => 'thumb',
        ]);

        $response = $this->actingAs($owner)->get($url);

        $response->assertOk();

        $cacheControl = (string) $response->headers->get('Cache-Control');

        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('immutable', $cacheControl);
    }

    public function test_an_upload_rejected_by_the_scanner_is_not_stored(): void
    {
        $this->app->instance(UploadScanner::class, new UploadScanner(
            command: 'clamdscan',
            failClosed: true,
            runner: static fn (string $command): array => ['exit' => 1, 'output' => 'Eicar-Test-Signature FOUND'],
        ));

        [$owner, $wedding] = $this->ownerWedding();

        $this->actingAs($owner)
            ->post(route('weddings.gallery.photos.store', $wedding), [
                'photos' => [UploadedFile::fake()->image('foto.png')],
            ])
            ->assertSessionHasErrors('photos');

        $this->assertDatabaseCount('media_assets', 0);
        $this->assertCount(0, Storage::disk('media')->allFiles());
    }

    public function test_a_failing_scanner_blocks_uploads_when_it_fails_closed(): void
    {
        $this->app->instance(UploadScanner::class, new UploadScanner(
            command: 'clamdscan',
            failClosed: true,
            runner: static fn (string $command): array => ['exit' => 2, 'output' => 'ERROR: cannot connect'],
        ));

        [$owner, $wedding] = $this->ownerWedding();

        $this->actingAs($owner)
            ->post(route('weddings.gallery.photos.store', $wedding), [
                'photos' => [UploadedFile::fake()->image('foto.png')],
            ])
            ->assertSessionHasErrors('photos');

        $this->assertDatabaseCount('media_assets', 0);
    }

    private function operator(string $secret): User
    {
        $admin = User::factory()->admin()->create();

        $admin->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => [],
        ])->save();

        return $admin;
    }

    /**
     * @return array{0: User, 1: Wedding}
     */
    private function ownerWedding(): array
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->for($owner, 'owner')->create();

        return [$owner, $wedding];
    }
}
