<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AnalyticsEvent;
use App\Models\InvitationPublication;
use App\Models\Lead;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\Wedding;
use App\Notifications\NewEnquiryReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Sprint 4 growth surface: contact leads with first-touch attribution, the
 * opt-in showcase, and moderated testimonials. Every public read path here is
 * either explicitly opted in by the owner or approved by an operator.
 */
final class MarketingGrowthTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_contact_form_stores_a_lead_with_first_touch_attribution(): void
    {
        $this->post(route('leads.store'), [
            'name' => 'Sari Wulandari',
            'email' => 'sari@example.test',
            'whatsapp' => '08123456789',
            'message' => 'Mau tanya paket untuk Desember.',
            'source' => 'landing',
            'utm_source' => 'instagram',
            'utm_medium' => 'social',
            'utm_campaign' => 'november',
        ])->assertRedirect()->assertSessionHas('lead_status');

        $lead = Lead::query()->sole();

        $this->assertSame('instagram', $lead->utm_source);
        $this->assertSame('social', $lead->utm_medium);
        $this->assertSame(Lead::STATUS_NEW, $lead->status);
        $this->assertDatabaseHas('analytics_events', ['name' => AnalyticsEvent::LEAD_CAPTURED]);
    }

    public function test_a_new_enquiry_is_emailed_to_the_studio_inbox(): void
    {
        Notification::fake();

        $this->post(route('leads.store'), [
            'name' => 'Sari Wulandari',
            'email' => 'sari@example.test',
            'whatsapp' => '08123456789',
            'message' => 'Mau tanya paket untuk Desember.',
        ])->assertRedirect()->assertSessionHas('lead_status');

        // Storing the lead is not enough: nobody refreshes an admin queue while
        // replying to customers, so the studio inbox is where it has to land.
        Notification::assertSentOnDemand(
            NewEnquiryReceived::class,
            static fn (NewEnquiryReceived $notification, array $channels, object $notifiable): bool => ($notifiable->routes['mail'] ?? null) === config('site.contact.email'),
        );
    }

    public function test_a_lead_can_carry_the_wedding_service_being_asked_about(): void
    {
        $this->post(route('leads.store'), [
            'name' => 'Sari Wulandari',
            'email' => 'sari@example.test',
            'message' => 'Butuh MUA untuk Desember, sekalian tema undangan.',
            'service_interest' => 'rias-pengantin',
        ])->assertRedirect()->assertSessionHas('lead_status');

        $lead = Lead::query()->sole();

        $this->assertSame('rias-pengantin', $lead->service_interest);
        // The admin queue and the mail show the label, not the slug, because the
        // person replying has to know what the customer meant without a lookup.
        $this->assertSame('Rias Pengantin', $lead->serviceInterestLabel());
    }

    public function test_a_service_interest_is_only_stored_when_the_site_offers_it(): void
    {
        $this->post(route('leads.store'), [
            'name' => 'Sari Wulandari',
            'email' => 'sari@example.test',
            'message' => 'Mau tanya layanan yang tidak ada.',
            'service_interest' => 'paket-umroh',
        ])->assertSessionHasErrors('service_interest');

        $this->assertDatabaseCount('leads', 0);
    }

    public function test_a_service_card_prefills_the_interest_on_the_form(): void
    {
        // Tapping "tanyakan layanan ini" has to arrive with the question already
        // answered, otherwise the section is decoration rather than a sales path.
        $content = $this->get(route('home', ['layanan' => 'dekorasi-pelaminan']))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/value="dekorasi-pelaminan"\s+selected/', $content);
    }

    public function test_the_chosen_service_survives_a_failed_submit(): void
    {
        $this->post(route('leads.store'), [
            'name' => 'Sari Wulandari',
            'service_interest' => 'dokumentasi',
        ])->assertSessionHasErrors('email');

        $content = $this->get(route('home'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/value="dokumentasi"\s+selected/', $content);
        $this->assertStringContainsString('Sari Wulandari', $content);
    }

    public function test_the_honeypot_pretends_success_without_storing_anything(): void
    {
        $this->post(route('leads.store'), [
            'name' => 'Bot',
            'email' => 'bot@example.test',
            'website' => 'https://spam.example.test',
        ])->assertRedirect()->assertSessionHas('lead_status');

        $this->assertDatabaseCount('leads', 0);
        $this->assertDatabaseCount('analytics_events', 0);
    }

    public function test_the_contact_form_validates_the_address(): void
    {
        $this->post(route('leads.store'), [
            'name' => 'Tanpa Email',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseCount('leads', 0);
    }

    public function test_the_landing_page_shows_only_approved_testimonials_and_opted_in_portfolio(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->for($owner, 'owner')->published()->create([
            'showcase_enabled' => true,
            'showcase_enabled_at' => now(),
        ]);
        InvitationPublication::factory()->for($wedding)->create(['published_by' => $owner]);

        Testimonial::factory()->approved()->create([
            'wedding_id' => $wedding->getKey(),
            'user_id' => $owner->getKey(),
            'rating' => 5,
            'quote' => 'Alur RSVP-nya jelas dan tamu tidak perlu memasang aplikasi apa pun.',
        ]);

        Testimonial::factory()->create([
            'wedding_id' => Wedding::factory()->for($owner, 'owner'),
            'user_id' => $owner->getKey(),
            'quote' => 'Kalimat ini masih menunggu peninjauan operator dan tidak boleh tampil.',
        ]);

        $response = $this->get(route('home'))->assertOk();

        $response->assertSee('Alur RSVP-nya jelas');
        $response->assertDontSee('masih menunggu peninjauan operator');
        $response->assertSee(route('showcase.show', ['slug' => $wedding->slug]), false);
        $response->assertSee(route('showcase.index'), false);
    }

    public function test_the_showcase_lists_only_opted_in_weddings_with_a_live_publication(): void
    {
        $visible = Wedding::factory()->published()->create([
            'showcase_enabled' => true,
            'showcase_enabled_at' => now(),
        ]);
        InvitationPublication::factory()->for($visible)->create();

        $notOptedIn = Wedding::factory()->published()->create();
        InvitationPublication::factory()->for($notOptedIn)->create();

        $optedInButUnpublished = Wedding::factory()->create([
            'showcase_enabled' => true,
            'showcase_enabled_at' => now(),
        ]);

        $response = $this->get(route('showcase.index'))->assertOk();

        $response->assertSee(route('showcase.show', ['slug' => $visible->slug]), false);
        $response->assertDontSee($notOptedIn->slug, false);
        $response->assertDontSee($optedInButUnpublished->slug, false);
    }

    public function test_a_showcase_page_renders_the_published_snapshot_with_its_watermark(): void
    {
        $wedding = Wedding::factory()->published()->create([
            'showcase_enabled' => true,
            'showcase_enabled_at' => now(),
        ]);
        InvitationPublication::factory()->for($wedding)->create();

        $url = route('showcase.show', ['slug' => $wedding->slug]);

        $response = $this->get($url)->assertOk();

        $response->assertSee('inv-showcase-bar', false);
        $response->assertSee('<link rel="canonical" href="'.$url.'">', false);
        $response->assertDontSee('noindex', false);
        $response->assertSee('Ayu &amp; Bima', false);
        $response->assertDontSee('Konfirmasi kehadiran');

        $this->assertDatabaseHas('analytics_events', [
            'name' => AnalyticsEvent::INVITATION_VIEWED,
            'wedding_id' => $wedding->getKey(),
        ]);
    }

    public function test_a_showcase_page_is_hidden_without_opt_in_or_publication(): void
    {
        $notOptedIn = Wedding::factory()->published()->create();
        InvitationPublication::factory()->for($notOptedIn)->create();

        $unpublished = Wedding::factory()->create([
            'showcase_enabled' => true,
            'showcase_enabled_at' => now(),
        ]);

        $this->get(route('showcase.show', ['slug' => $notOptedIn->slug]))->assertNotFound();
        $this->get(route('showcase.show', ['slug' => $unpublished->slug]))->assertNotFound();
    }

    public function test_the_owner_can_opt_in_only_after_publishing_and_the_change_is_audited(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->for($owner, 'owner')->published()->create();

        $this->actingAs($owner)
            ->post(route('weddings.showcase.update', $wedding), ['showcase_enabled' => '1'])
            ->assertSessionHasErrors('showcase');

        $this->assertFalse($wedding->fresh()->showcase_enabled);

        InvitationPublication::factory()->for($wedding)->create(['published_by' => $owner]);

        $this->actingAs($owner)
            ->post(route('weddings.showcase.update', $wedding), ['showcase_enabled' => '1'])
            ->assertSessionHasNoErrors();

        $this->assertTrue($wedding->fresh()->showcase_enabled);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'wedding.showcase_enabled',
            'resource_id' => (string) $wedding->getKey(),
        ]);

        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->post(route('weddings.showcase.update', $wedding), ['showcase_enabled' => '0'])
            ->assertForbidden();
    }

    public function test_a_testimonial_requires_a_publication_and_editing_sends_it_back_to_moderation(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->for($owner, 'owner')->published()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($owner)
            ->post(route('weddings.testimonial.store', $wedding), [
                'rating' => 5,
                'quote' => 'Undangan rapi dan tamu mudah mengonfirmasi kehadiran.',
            ])
            ->assertSessionHasErrors('testimonial');

        InvitationPublication::factory()->for($wedding)->create(['published_by' => $owner]);

        $this->actingAs($owner)
            ->post(route('weddings.testimonial.store', $wedding), [
                'rating' => 5,
                'quote' => 'Undangan rapi dan tamu mudah mengonfirmasi kehadiran.',
            ])
            ->assertSessionHasNoErrors();

        $testimonial = Testimonial::query()->sole();

        $this->assertSame(Testimonial::STATUS_PENDING, $testimonial->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'testimonial.submitted']);

        $this->actingAs($admin)
            ->post(route('admin.testimonials.approve', $testimonial))
            ->assertRedirect();

        $this->assertSame(Testimonial::STATUS_APPROVED, $testimonial->fresh()->status);
        $this->assertNotNull($testimonial->fresh()->moderated_at);

        // Editing an approved quote must not publish new words silently.
        $this->actingAs($owner)
            ->post(route('weddings.testimonial.store', $wedding), [
                'rating' => 4,
                'quote' => 'Versi baru yang belum ditinjau operator mana pun.',
            ])
            ->assertSessionHasNoErrors();

        $testimonial->refresh();

        $this->assertSame(Testimonial::STATUS_PENDING, $testimonial->status);
        $this->assertNull($testimonial->moderated_at);
        $this->assertSame(4, $testimonial->rating);
    }

    public function test_only_an_operator_can_moderate_and_read_the_analytics_funnel(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create();
        $testimonial = Testimonial::factory()->create();

        AnalyticsEvent::create([
            'name' => AnalyticsEvent::LANDING_VIEWED,
            'properties' => null,
            'occurred_at' => now(),
        ]);

        $this->actingAs($admin)->get(route('admin.testimonials.index'))
            ->assertOk()
            ->assertSee('Menunggu');

        $this->actingAs($admin)->get(route('admin.analytics.index'))
            ->assertOk()
            ->assertSee('Landing dilihat')
            ->assertSee('Konversi antar langkah');

        $this->actingAs($customer)->get(route('admin.testimonials.index'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.analytics.index'))->assertForbidden();
        $this->actingAs($customer)->post(route('admin.testimonials.approve', $testimonial))->assertForbidden();

        $this->assertSame(Testimonial::STATUS_PENDING, $testimonial->fresh()->status);
    }

    public function test_visiting_the_landing_page_and_registering_record_funnel_events(): void
    {
        $this->get(route('home'))->assertOk();

        $this->post(route('register'), [
            'name' => 'Pendaftar Baru',
            'email' => 'pendaftar@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect(route('verification.notice'));

        $this->assertDatabaseHas('analytics_events', ['name' => AnalyticsEvent::LANDING_VIEWED]);
        $this->assertDatabaseHas('analytics_events', ['name' => AnalyticsEvent::REGISTER_COMPLETED]);

        // The funnel is aggregate-only: no row may carry a person identifier.
        AnalyticsEvent::query()->each(function (AnalyticsEvent $event): void {
            $this->assertArrayNotHasKey('user_id', $event->getAttributes());
            $this->assertArrayNotHasKey('ip_address', $event->getAttributes());
        });
    }
}
