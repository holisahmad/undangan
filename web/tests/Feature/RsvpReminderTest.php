<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\GuestInvitationLink;
use App\Models\InvitationPublication;
use App\Models\Rsvp;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingEvent;
use App\Notifications\RsvpReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\Support\RendersMail;
use Tests\TestCase;

/**
 * Sprint 3: RSVP reminders reach only guests who supplied an address, have not
 * answered, and are outside the cooldown window; publishing is required.
 */
final class RsvpReminderTest extends TestCase
{
    use RefreshDatabase;
    use RendersMail;

    public function test_the_owner_can_remind_guests_that_have_not_answered(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $wedding = $this->publishedWedding($owner);

        $target = Guest::factory()->for($wedding)->create([
            'display_name' => 'Tamu Diingatkan',
            'contact_email' => 'tamu@example.test',
        ]);

        // Skipped: no address, already answered, or reminded a day ago.
        Guest::factory()->for($wedding)->create();
        $answered = Guest::factory()->for($wedding)->create(['contact_email' => 'sudah@example.test']);
        Rsvp::factory()->for($answered)->create();
        Guest::factory()->for($wedding)->create([
            'contact_email' => 'baru@example.test',
            'rsvp_reminded_at' => now()->subDay(),
        ]);

        $this->actingAs($owner)
            ->post(route('weddings.guests.remind', $wedding))
            ->assertRedirect(route('weddings.guests.index', $wedding))
            ->assertSessionHas('status');

        Notification::assertSentOnDemandTimes(RsvpReminderNotification::class, 1);
        Notification::assertSentOnDemand(
            RsvpReminderNotification::class,
            static fn (RsvpReminderNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool => ($notifiable->routes['mail'] ?? null) === 'tamu@example.test',
        );

        $this->assertNotNull($target->refresh()->rsvp_reminded_at);
        $this->assertTrue(
            GuestInvitationLink::query()
                ->where('guest_id', $target->getKey())
                ->whereNull('revoked_at')
                ->exists(),
        );
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'guest.reminders_sent',
            'resource_id' => $wedding->getKey(),
        ]);
    }

    public function test_the_cooldown_keeps_repeat_runs_quiet_until_forced(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $wedding = $this->publishedWedding($owner);
        $guest = Guest::factory()->for($wedding)->create([
            'contact_email' => 'tamu@example.test',
            'rsvp_reminded_at' => now()->subDays(2),
        ]);

        $this->actingAs($owner)
            ->post(route('weddings.guests.remind', $wedding))
            ->assertSessionHas('status');

        Notification::assertNothingSent();
        $this->assertTrue($guest->refresh()->rsvp_reminded_at->isBefore(now()->subDay()));

        $this->artisan('rsvp:remind', ['--wedding' => $wedding->slug, '--force' => true])->assertSuccessful();

        Notification::assertSentOnDemandTimes(RsvpReminderNotification::class, 1);
        $this->assertTrue($guest->refresh()->rsvp_reminded_at->isAfter(now()->subMinute()));
    }

    public function test_reminders_require_a_published_invitation(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $wedding = Wedding::factory()->for($owner, 'owner')->create();
        Guest::factory()->for($wedding)->create(['contact_email' => 'tamu@example.test']);

        $this->actingAs($owner)
            ->get(route('weddings.guests.index', $wedding))
            ->assertOk()
            ->assertDontSee('Kirim pengingat');

        $this->actingAs($owner)
            ->post(route('weddings.guests.remind', $wedding))
            ->assertSessionHasErrors('reminders');

        $this->artisan('rsvp:remind', ['--wedding' => $wedding->slug])->assertFailed();

        Notification::assertNothingSent();
        $this->assertDatabaseCount('guest_invitation_links', 0);
    }

    public function test_another_account_cannot_trigger_reminders(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $wedding = $this->publishedWedding($owner);
        Guest::factory()->for($wedding)->create(['contact_email' => 'tamu@example.test']);

        $this->actingAs(User::factory()->create())
            ->post(route('weddings.guests.remind', $wedding))
            ->assertForbidden();

        Notification::assertNothingSent();
        $this->assertDatabaseCount('guest_invitation_links', 0);
    }

    public function test_the_command_dry_run_counts_without_sending_and_skips_drafts(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $published = $this->publishedWedding($owner);
        $first = Guest::factory()->for($published)->create(['contact_email' => 'satu@example.test']);
        $second = Guest::factory()->for($published)->create(['contact_email' => 'dua@example.test']);

        $draft = Wedding::factory()->for(User::factory()->create(), 'owner')->create();
        Guest::factory()->for($draft)->create(['contact_email' => 'draf@example.test']);

        $this->artisan('rsvp:remind', ['--dry-run' => true])
            ->expectsOutputToContain('2 tamu akan diingatkan')
            ->assertSuccessful();

        Notification::assertNothingSent();
        $this->assertNull($first->refresh()->rsvp_reminded_at);
        $this->assertNull($second->refresh()->rsvp_reminded_at);

        $this->artisan('rsvp:remind')->assertSuccessful();

        Notification::assertSentOnDemandTimes(RsvpReminderNotification::class, 2);
        $this->assertNotNull($first->refresh()->rsvp_reminded_at);
        $this->assertNotNull($second->refresh()->rsvp_reminded_at);
    }

    public function test_the_reminder_email_names_the_event_and_carries_the_personal_link(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->for($owner, 'owner')->published()->create([
            'internal_title' => 'Pernikahan Ayu & Bima',
        ]);
        $guest = Guest::factory()->for($wedding)->create(['display_name' => 'Bapak Ahmad']);

        WeddingEvent::factory()->for($wedding)->create([
            'title' => 'Akad Nikah',
            'starts_at' => now()->addMonth()->setTime(9, 0),
            'venue_name' => 'Gedung Serbaguna',
        ]);

        $url = 'https://undangan.test/g/token-uji';
        $mail = (new RsvpReminderNotification($wedding->refresh(), $guest, $url))->toMail($guest);
        $html = $this->renderMail($mail);

        $this->assertSame('Konfirmasi kehadiran — Pernikahan Ayu & Bima', $mail->subject);
        $this->assertStringContainsString('Halo Bapak Ahmad, satu langkah lagi', $html);
        $this->assertStringContainsString('Kami belum menerima konfirmasi kehadiran Anda', $html);
        $this->assertStringContainsString('Akad Nikah', $html);
        $this->assertStringContainsString('Gedung Serbaguna', $html);
        $this->assertStringContainsString('menggantikan tautan undangan sebelumnya', $html);
        $this->assertStringContainsString('href="'.$url.'"', $html);
    }

    private function publishedWedding(User $owner): Wedding
    {
        $wedding = Wedding::factory()->for($owner, 'owner')->published()->create();

        InvitationPublication::factory()->for($wedding)->create();

        return $wedding;
    }
}
