<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RsvpStatus;
use App\Models\Guest;
use App\Models\GuestInvitationLink;
use App\Models\InvitationPublication;
use App\Models\MediaAlbum;
use App\Models\MediaAsset;
use App\Models\Rsvp;
use App\Models\Wedding;
use App\Support\Guests\GuestToken;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guest-facing personal link and RSVP. The token is the only credential, so
 * these tests also cover revocation, expiry and party-size limits.
 */
final class GuestInvitationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TemplateSeeder::class);
    }

    public function test_a_valid_token_shows_the_personalised_invitation_without_contact_details(): void
    {
        [$wedding, $guest, $token] = $this->publishedGuest();

        $response = $this->get(route('guests.invitation.show', ['token' => $token]));

        $response->assertOk()
            ->assertSee('Bapak Ahmad')
            ->assertSee('Ayu')
            ->assertSee('Konfirmasi kehadiran')
            ->assertDontSee('081200000001')
            ->assertDontSee('ahmad@example.test')
            ->assertDontSee($wedding->internal_title);

        $link = GuestInvitationLink::query()->sole();

        $this->assertSame(1, $link->view_count);
        $this->assertNotNull($link->last_used_at);
        $this->assertSame($guest->getKey(), $link->guest_id);
    }

    public function test_the_personal_page_does_not_list_other_guests(): void
    {
        [, $guest] = $this->publishedGuest();

        $other = Guest::factory()->for($guest->wedding)->create([
            'display_name' => 'Tamu Lain',
            'contact_phone' => '081299999999',
        ]);

        $token = GuestToken::generate();
        GuestInvitationLink::factory()->for($guest)->withToken($token)->create();

        $this->get(route('guests.invitation.show', ['token' => $token]))
            ->assertOk()
            ->assertDontSee($other->display_name)
            ->assertDontSee('081299999999');
    }

    public function test_the_personal_page_renders_the_published_template_and_share_metadata(): void
    {
        [, , $token] = $this->publishedGuest();

        $this->get(route('guests.invitation.show', ['token' => $token]))
            ->assertOk()
            ->assertSee('data-template="classic-ivory.v1"', false)
            ->assertSee('<meta property="og:title" content="Ayu &amp; Bima">', false)
            ->assertSee('<meta name="description"', false)
            ->assertSee('<meta name="robots" content="noindex,nofollow">', false)
            ->assertSee('Kepada')
            ->assertSee('Tautan pribadi');
    }

    public function test_the_personal_page_links_the_gallery_only_when_photos_are_public(): void
    {
        [$wedding, , $token] = $this->publishedGuest();
        $url = route('guests.invitation.show', ['token' => $token]);

        $this->get($url)->assertOk()->assertDontSee('Lihat galeri foto');

        $private = MediaAlbum::factory()->for($wedding)->create();
        MediaAsset::factory()->for($wedding)->create(['album_id' => $private->getKey()]);

        $this->get($url)->assertOk()->assertDontSee('Lihat galeri foto');

        $public = MediaAlbum::factory()->for($wedding)->public()->create();
        MediaAsset::factory()->for($wedding)->create(['album_id' => $public->getKey()]);

        $this->get($url)
            ->assertOk()
            ->assertSee('Lihat galeri foto')
            ->assertSee(route('invitations.gallery', ['slug' => $wedding->slug]), false);
    }

    public function test_an_unknown_token_returns_not_found(): void
    {
        $this->publishedGuest();

        $this->get(route('guests.invitation.show', ['token' => GuestToken::generate()]))
            ->assertNotFound();
    }

    public function test_a_revoked_token_returns_not_found(): void
    {
        [, , $token] = $this->publishedGuest();

        GuestInvitationLink::query()->sole()->update(['revoked_at' => now()]);

        $this->get(route('guests.invitation.show', ['token' => $token]))->assertNotFound();
    }

    public function test_an_expired_token_returns_not_found(): void
    {
        [, , $token] = $this->publishedGuest();

        GuestInvitationLink::query()->sole()->update(['expires_at' => now()->subMinute()]);

        $this->get(route('guests.invitation.show', ['token' => $token]))->assertNotFound();
    }

    public function test_an_unpublished_wedding_returns_gone_instead_of_the_invitation(): void
    {
        [$wedding, , $token] = $this->publishedGuest();

        $wedding->publications()->update(['unpublished_at' => now()]);

        $this->get(route('guests.invitation.show', ['token' => $token]))
            ->assertStatus(410)
            ->assertDontSee('Ayu Lestari');
    }

    public function test_a_guest_submits_an_rsvp_within_the_party_limit(): void
    {
        [, $guest, $token] = $this->publishedGuest(allowedPartySize: 2);

        $response = $this->post(route('guests.invitation.rsvp', ['token' => $token]), [
            'status' => 'attending',
            'party_size' => 2,
            'message' => 'Kami berdua akan hadir.',
        ]);

        $response
            ->assertRedirect(route('guests.invitation.show', ['token' => $token]))
            ->assertSessionHas('rsvp_saved');

        $rsvp = Rsvp::query()->sole();

        $this->assertSame(RsvpStatus::Attending, $rsvp->status);
        $this->assertSame(2, $rsvp->party_size);
        $this->assertSame('Kami berdua akan hadir.', $rsvp->message);
        $this->assertSame($guest->getKey(), $rsvp->guest_id);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'rsvp.submitted',
            'resource_id' => $guest->getKey(),
        ]);

        $this->get(route('guests.invitation.show', ['token' => $token]))
            ->assertOk()
            ->assertSee('Terima kasih')
            ->assertSee('Respons terakhir Anda');
    }

    public function test_the_party_size_cannot_exceed_the_guest_limit(): void
    {
        [, , $token] = $this->publishedGuest(allowedPartySize: 2);

        $this->post(route('guests.invitation.rsvp', ['token' => $token]), [
            'status' => 'attending',
            'party_size' => 3,
        ])->assertSessionHasErrors('party_size');

        $this->assertDatabaseCount('rsvps', 0);
    }

    public function test_attending_requires_a_party_size(): void
    {
        [, , $token] = $this->publishedGuest();

        $this->post(route('guests.invitation.rsvp', ['token' => $token]), [
            'status' => 'attending',
        ])->assertSessionHasErrors('party_size');

        $this->assertDatabaseCount('rsvps', 0);
    }

    public function test_an_unsupported_status_is_rejected(): void
    {
        [, , $token] = $this->publishedGuest();

        $this->post(route('guests.invitation.rsvp', ['token' => $token]), [
            'status' => 'probably',
        ])->assertSessionHasErrors('status');

        $this->assertDatabaseCount('rsvps', 0);
    }

    public function test_a_later_response_replaces_the_previous_one(): void
    {
        [, $guest, $token] = $this->publishedGuest(allowedPartySize: 2);

        $this->post(route('guests.invitation.rsvp', ['token' => $token]), [
            'status' => 'attending',
            'party_size' => 2,
        ])->assertSessionHas('rsvp_saved');

        $this->post(route('guests.invitation.rsvp', ['token' => $token]), [
            'status' => 'declined',
        ])->assertSessionHas('rsvp_saved');

        $this->assertSame(1, Rsvp::query()->where('guest_id', $guest->getKey())->count());

        $rsvp = Rsvp::query()->sole();

        $this->assertSame(RsvpStatus::Declined, $rsvp->status);
        $this->assertSame(0, $rsvp->party_size);
        $this->assertNull($rsvp->message);
    }

    public function test_an_rsvp_is_refused_while_the_invitation_is_not_published(): void
    {
        [$wedding, , $token] = $this->publishedGuest();

        $wedding->publications()->update(['unpublished_at' => now()]);

        $this->post(route('guests.invitation.rsvp', ['token' => $token]), [
            'status' => 'attending',
            'party_size' => 1,
        ])->assertStatus(410);

        $this->assertDatabaseCount('rsvps', 0);
    }

    public function test_rsvp_submissions_are_rate_limited_per_ip(): void
    {
        config(['platform.guests.rsvp_rate_limit_per_minute' => 2]);

        [, , $token] = $this->publishedGuest();

        $payload = ['status' => 'attending', 'party_size' => 1];

        $this->post(route('guests.invitation.rsvp', ['token' => $token]), $payload)->assertRedirect();
        $this->post(route('guests.invitation.rsvp', ['token' => $token]), $payload)->assertRedirect();
        $this->post(route('guests.invitation.rsvp', ['token' => $token]), $payload)->assertStatus(429);
    }

    public function test_the_personal_page_is_rate_limited_per_ip(): void
    {
        config(['platform.guests.rate_limit_per_minute' => 2]);

        [, , $token] = $this->publishedGuest();

        $url = route('guests.invitation.show', ['token' => $token]);

        $this->get($url)->assertOk();
        $this->get($url)->assertOk();
        $this->get($url)->assertStatus(429);
    }

    /**
     * @return array{0: Wedding, 1: Guest, 2: string}
     */
    private function publishedGuest(int $allowedPartySize = 2): array
    {
        $wedding = Wedding::factory()->published()->create();
        InvitationPublication::factory()->for($wedding)->create();

        $guest = Guest::factory()->for($wedding)->create([
            'display_name' => 'Bapak Ahmad',
            'contact_phone' => '081200000001',
            'contact_email' => 'ahmad@example.test',
            'allowed_party_size' => $allowedPartySize,
        ]);

        $token = GuestToken::generate();
        GuestInvitationLink::factory()->for($guest)->withToken($token)->create();

        return [$wedding, $guest, $token];
    }
}
