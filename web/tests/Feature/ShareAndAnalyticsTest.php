<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RsvpStatus;
use App\Models\Guest;
use App\Models\InvitationPublication;
use App\Models\Rsvp;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 7 growth slice: the RSVP summary is computed from one grouped query and
 * the share panel only appears once the invitation is actually published.
 */
final class ShareAndAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_rsvp_summary_counts_every_state(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->for($owner, 'owner')->published()->create();

        $attending = Guest::factory()->for($wedding)->create(['display_name' => 'Hadir']);
        Rsvp::factory()->for($attending)->create([
            'status' => RsvpStatus::Attending,
            'party_size' => 3,
        ]);

        $declined = Guest::factory()->for($wedding)->create(['display_name' => 'Tidak Hadir']);
        Rsvp::factory()->for($declined)->declined()->create();

        $maybe = Guest::factory()->for($wedding)->create(['display_name' => 'Ragu']);
        Rsvp::factory()->for($maybe)->create([
            'status' => RsvpStatus::Maybe,
            'party_size' => 1,
        ]);

        Guest::factory()->for($wedding)->create(['display_name' => 'Belum Menjawab']);

        $response = $this->actingAs($owner)
            ->get(route('weddings.show', $wedding))
            ->assertOk();

        $response->assertViewHas('rsvp', static fn (array $rsvp): bool => $rsvp === [
            'invited' => 4,
            'responded' => 3,
            'pending' => 1,
            'attending' => 1,
            'declined' => 1,
            'maybe' => 1,
            'attending_people' => 3,
            'response_rate' => 75,
        ]);

        $response->assertSee('3 dari 4 tamu sudah merespons');
        $response->assertSee('75%');
        $response->assertSee('style="width: 75%;"', false);
    }

    public function test_the_share_panel_only_appears_for_a_published_invitation(): void
    {
        $owner = User::factory()->create();
        $draft = Wedding::factory()->for($owner, 'owner')->create();

        $this->actingAs($owner)
            ->get(route('weddings.show', $draft))
            ->assertOk()
            ->assertDontSee('Bagikan undangan')
            // The support footer links WhatsApp on every page, so the share
            // sheet is the marker that must be absent.
            ->assertDontSee('wa.me/?text=');

        $published = Wedding::factory()->for($owner, 'owner')->published()->create();
        InvitationPublication::factory()->for($published)->create();

        $this->actingAs($owner)
            ->get(route('weddings.show', $published))
            ->assertOk()
            ->assertSee('Bagikan undangan')
            ->assertSee('wa.me/?text=')
            ->assertSee('Salin tautan')
            ->assertSee(route('invitations.public', ['slug' => $published->slug]));
    }

    public function test_the_share_panel_provides_ready_to_send_copy(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->for($owner, 'owner')->published()->create([
            'internal_title' => 'Pernikahan Ayu & Bima',
        ]);
        InvitationPublication::factory()->for($wedding)->create();
        WeddingEvent::factory()->for($wedding)->create([
            'starts_at' => now()->addMonth()->setTime(9, 0),
            'venue_name' => 'Gedung Serbaguna',
        ]);

        $this->actingAs($owner)
            ->get(route('weddings.show', $wedding))
            ->assertOk()
            ->assertSee('Salin teks undangan')
            ->assertSee('Bagikan via WhatsApp')
            ->assertSee('Undangan pernikahan Pernikahan Ayu & Bima')
            ->assertSee('Gedung Serbaguna')
            ->assertSee('Buka undangan digital: '.route('invitations.public', ['slug' => $wedding->slug]));
    }
}
