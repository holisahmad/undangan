<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ArchiveStatus;
use App\Enums\RsvpStatus;
use App\Models\ArchiveJob;
use App\Models\Guest;
use App\Models\MediaAlbum;
use App\Models\MediaAsset;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Rsvp;
use App\Models\TemplateVersion;
use App\Models\User;
use App\Models\Wedding;
use Database\Seeders\PlanSeeder;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * One journey through the product the way a customer and a guest experience it:
 * register, build, pay, publish, invite, RSVP, share, upload, archive, export.
 */
final class EndToEndJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([TemplateSeeder::class, PlanSeeder::class]);

        Storage::fake('media');

        // The journey pays through the sandbox provider so the signed
        // notification path is exercised end to end; both switches are needed
        // because PAYMENT_FAKE_ENABLED is off in .env.
        config([
            'platform.payments.default_gateway' => 'fake',
            'platform.payments.fake.enabled' => true,
        ]);
    }

    public function test_a_customer_and_a_guest_complete_the_whole_journey(): void
    {
        $this->post('/register', [
            'name' => 'Rina Pemilik',
            'email' => 'rina@example.test',
            'password' => 'rahasia-kuat-123',
            'password_confirmation' => 'rahasia-kuat-123',
        ])->assertRedirect(route('verification.notice'));

        $owner = User::query()->sole();
        $this->assertAuthenticatedAs($owner);
        $this->assertFalse($owner->hasVerifiedEmail());

        // The customer area stays locked until the inbox link is opened.
        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));

        $this->actingAs($owner)
            ->get(URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
                'id' => $owner->getKey(),
                'hash' => sha1($owner->email),
            ]))
            // The earlier redirect stored /dashboard as the intended URL, so the
            // query flag may or may not be appended.
            ->assertRedirectContains(route('dashboard'));

        $this->assertTrue($owner->refresh()->hasVerifiedEmail());

        $templateVersion = TemplateVersion::query()->firstOrFail();

        $this->actingAs($owner)
            ->post(route('weddings.store'), [
                'internal_title' => 'Pernikahan Rina & Dedi',
                'template_version_id' => $templateVersion->getKey(),
            ])
            ->assertRedirect();

        $wedding = Wedding::query()->sole();

        $this->actingAs($owner)->post(route('weddings.partners.store', $wedding), [
            'partner_type' => 'bride',
            'display_name' => 'Rina Lestari',
            'nickname' => 'Rina',
        ])->assertSessionHasNoErrors();

        $this->actingAs($owner)->post(route('weddings.partners.store', $wedding), [
            'partner_type' => 'groom',
            'display_name' => 'Dedi Pratama',
            'nickname' => 'Dedi',
        ])->assertSessionHasNoErrors();

        $this->actingAs($owner)->post(route('weddings.events.store', $wedding), [
            'event_type' => 'resepsi',
            'title' => 'Resepsi',
            'starts_at' => now()->addMonth()->format('Y-m-d\TH:i'),
            'timezone' => 'Asia/Jakarta',
            'venue_name' => 'Gedung Serbaguna',
            'address' => 'Jalan Melati 10',
        ])->assertSessionHasNoErrors();

        // Payment: the browser only ever supplies the plan, never the amount.
        $plan = Plan::query()->where('code', 'essential')->firstOrFail();

        $this->actingAs($owner)
            ->post(route('weddings.orders.store', $wedding), ['plan_id' => $plan->getKey()])
            ->assertRedirect();

        $order = Order::query()->sole();

        $this->actingAs($owner)
            ->post(route('orders.checkout', $order))
            ->assertRedirectContains(route('checkout.fake.show', $order));

        $this->actingAs($owner)
            ->post(route('checkout.fake.complete', $order), ['status' => 'succeeded'])
            ->assertRedirect(route('orders.show', $order));

        $this->assertTrue($order->refresh()->isPaid());
        $this->assertTrue($wedding->refresh()->activeInvitationEntitlement() !== null);

        // Publish freezes the snapshot the public page will serve.
        $this->actingAs($owner)
            ->post(route('weddings.publish', $wedding))
            ->assertRedirect(route('weddings.show', $wedding));

        $publicUrl = route('invitations.public', ['slug' => $wedding->slug]);

        $this->get($publicUrl)
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->actingAs($owner)
            ->get(route('weddings.show', $wedding))
            ->assertOk()
            ->assertSee('Bagikan undangan')
            ->assertSee('wa.me')
            ->assertSee('Salin tautan');

        // Guest, personal link, and the RSVP the guest submits themselves.
        $this->actingAs($owner)->post(route('weddings.guests.store', $wedding), [
            'display_name' => 'Budi Santoso',
            'contact_phone' => '081200000001',
            'allowed_party_size' => 4,
        ])->assertSessionHasNoErrors();

        $guest = Guest::query()->sole();

        $this->actingAs($owner)
            ->post(route('weddings.guests.link.store', [$wedding, $guest]))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('guest_link');

        $personalUrl = (string) session('guest_link');
        $token = basename((string) parse_url($personalUrl, PHP_URL_PATH));

        $this->get($personalUrl)
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        $this->post(route('guests.invitation.rsvp', ['token' => $token]), [
            'status' => RsvpStatus::Attending->value,
            'party_size' => 2,
            'message' => 'Terima kasih atas undangannya.',
        ])->assertRedirect(route('guests.invitation.show', ['token' => $token]));

        $this->assertDatabaseHas('rsvps', [
            'guest_id' => $guest->getKey(),
            'status' => RsvpStatus::Attending->value,
            'party_size' => 2,
        ]);

        // Album, upload, public gallery, and a temporary archive.
        $this->actingAs($owner)->post(route('weddings.gallery.albums.store', $wedding), [
            'title' => 'Resepsi',
            'visibility' => 'public',
        ])->assertSessionHasNoErrors();

        $album = MediaAlbum::query()->sole();

        $this->actingAs($owner)->post(route('weddings.gallery.photos.store', $wedding), [
            'album_id' => $album->getKey(),
            'photos' => [UploadedFile::fake()->image('resepsi.png', 1200, 900)],
        ])->assertSessionHasNoErrors();

        $asset = MediaAsset::query()->sole();

        $this->assertTrue(Storage::disk('media')->exists((string) $asset->object_key));

        $this->get(route('invitations.gallery', ['slug' => $wedding->slug]))
            ->assertOk()
            ->assertSee('Resepsi')
            ->assertSee('resepsi.png');

        $this->actingAs($owner)
            ->post(route('weddings.gallery.archives.store', $wedding))
            ->assertSessionHasNoErrors();

        $archive = ArchiveJob::query()->sole();

        $this->assertSame(ArchiveStatus::Ready, $archive->status);

        $download = $this->get(URL::temporarySignedRoute('media.archives.download', now()->addMinutes(10), [
            'archive' => $archive->getKey(),
        ]));

        $download->assertOk()->assertHeader('Content-Type', 'application/zip');
        $this->assertStringStartsWith('PK', $download->streamedContent());

        // Export never leaks the personal token.
        $csv = $this->actingAs($owner)
            ->get(route('weddings.guests.export', $wedding))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Budi Santoso', $csv);
        $this->assertStringNotContainsString($token, $csv);

        $this->assertDatabaseHas('audit_logs', ['action' => 'invitation.published']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'media.uploaded']);

        $this->assertSame(1, Rsvp::query()->count());

        $this->get('/up')->assertOk();
    }
}
