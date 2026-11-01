<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The event form feeds its timezone straight into Carbon, so an unknown value
 * has to fail validation instead of surfacing as a server error
 * (docs/SECURITY.md, section 4).
 */
final class WeddingEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_owner_can_store_an_event_with_a_known_timezone(): void
    {
        $owner = $this->owner();
        $wedding = $this->wedding($owner);

        $this->actingAs($owner)
            ->post(route('weddings.events.store', $wedding), $this->eventPayload())
            ->assertRedirect(route('weddings.edit', $wedding));

        $event = $wedding->events()->sole();

        $this->assertSame('Asia/Jakarta', $event->timezone);
        // The datetime-local input is read in the event timezone and stored UTC.
        $this->assertSame('03:00', $event->starts_at->setTimezone('UTC')->format('H:i'));
    }

    public function test_an_unknown_timezone_is_rejected_instead_of_breaking_the_save(): void
    {
        $owner = $this->owner();
        $wedding = $this->wedding($owner);

        $this->actingAs($owner)
            ->post(route('weddings.events.store', $wedding), $this->eventPayload(['timezone' => 'Not/AZone']))
            ->assertSessionHasErrors('timezone');

        $this->assertDatabaseCount('wedding_events', 0);
    }

    public function test_an_unknown_timezone_cannot_break_an_existing_event(): void
    {
        $owner = $this->owner();
        $wedding = $this->wedding($owner);

        $this->actingAs($owner)
            ->post(route('weddings.events.store', $wedding), $this->eventPayload())
            ->assertRedirect();

        $event = $wedding->events()->sole();

        $this->actingAs($owner)
            ->patch(route('weddings.events.update', [$wedding, $event]), $this->eventPayload(['timezone' => 'Mars/Olympus']))
            ->assertSessionHasErrors('timezone');

        $this->assertSame('Asia/Jakarta', $event->refresh()->timezone);

        // A valid update still works after the rejected attempt.
        $this->actingAs($owner)
            ->patch(route('weddings.events.update', [$wedding, $event]), $this->eventPayload(['title' => 'Resepsi Malam']))
            ->assertRedirect();

        $this->assertSame('Resepsi Malam', $event->refresh()->title);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function eventPayload(array $overrides = []): array
    {
        return array_merge([
            'event_type' => 'resepsi',
            'title' => 'Resepsi',
            'starts_at' => now()->addMonth()->setTime(10, 0)->format('Y-m-d\TH:i'),
            'timezone' => 'Asia/Jakarta',
            'venue_name' => 'Gedung Serbaguna',
            'address' => 'Jalan Melati 10',
        ], $overrides);
    }

    private function owner(): User
    {
        return User::factory()->create();
    }

    private function wedding(User $owner): Wedding
    {
        return Wedding::factory()->for($owner, 'owner')->create();
    }
}
