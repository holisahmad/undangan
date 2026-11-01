<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Entitlements\GrantInvitationEntitlement;
use App\Actions\Invitations\PublishInvitation;
use App\Actions\Weddings\CreateWedding;
use App\Models\InvitationTemplate;
use App\Models\TemplateVersion;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingEvent;
use App\Models\WeddingPartner;
use App\Support\Templates\TemplateRegistry;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TemplateCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_seeder_is_idempotent_and_ships_every_renderer(): void
    {
        $this->seed(TemplateSeeder::class);
        $this->seed(TemplateSeeder::class);

        $registry = app(TemplateRegistry::class);

        // Catalogue and code ship as one unit: a row per renderer, one published
        // version per row, and no renderer that the catalogue forgot to name.
        $this->assertSame(count($registry->rendererKeys()), InvitationTemplate::query()->count());
        $this->assertSame(count($registry->rendererKeys()), TemplateVersion::query()->where('status', 'published')->count());

        foreach ($registry->rendererKeys() as $key) {
            $this->assertSame(1, TemplateVersion::query()->where('renderer_key', $key)->count(), $key);
        }
    }

    public function test_every_published_version_has_a_renderer_and_a_view(): void
    {
        $this->seed(TemplateSeeder::class);

        $registry = app(TemplateRegistry::class);

        foreach (TemplateVersion::query()->where('status', 'published')->get() as $version) {
            $this->assertTrue($registry->supports($version->renderer_key), $version->renderer_key);
            $this->assertTrue(view()->exists($registry->viewFor($version->renderer_key)), $version->renderer_key);
        }
    }

    public function test_the_catalogue_offers_a_choice_at_every_package_level(): void
    {
        $this->seed(TemplateSeeder::class);

        // The pricing page promises exclusive designs, so a tier that stayed
        // empty would make that promise a lie and the filter useless.
        foreach (array_keys(InvitationTemplate::TIER_RANKS) as $tier) {
            $this->assertGreaterThan(0, InvitationTemplate::query()->where('tier', $tier)->count(), $tier);
        }
    }

    public function test_a_premium_design_is_flagged_as_exclusive(): void
    {
        $this->seed(TemplateSeeder::class);

        $premium = InvitationTemplate::query()->where('tier', InvitationTemplate::TIER_PREMIUM)->get();

        foreach ($premium as $template) {
            $this->assertTrue($template->isExclusivelyPremium(), $template->slug);
        }

        $this->assertFalse(
            InvitationTemplate::query()->where('tier', InvitationTemplate::TIER_ESSENTIAL)->first()->isExclusivelyPremium()
        );
    }

    public function test_each_template_renders_its_own_view(): void
    {
        $this->seed(TemplateSeeder::class);

        foreach (InvitationTemplate::query()->pluck('slug') as $slug) {
            $wedding = $this->publishWedding($slug);

            $this->get(route('invitations.public', ['slug' => $wedding->slug]))
                ->assertOk()
                ->assertSee('data-template="'.$slug.'.v1"', false)
                ->assertSee('Ayu Lestari')
                ->assertSee('Bima Pratama');
        }
    }

    public function test_the_emerald_template_prints_a_scannable_link_behind_a_cover(): void
    {
        $this->seed(TemplateSeeder::class);

        $wedding = $this->publishWedding('emerald-gilded');

        $response = $this->get(route('invitations.public', ['slug' => $wedding->slug]))->assertOk();

        // The cover is a checkbox and a label: the invitation opens with no
        // JavaScript, so a guest never depends on our bundle to read it.
        $response->assertSee('id="emerald-cover"', false);
        $response->assertSee('for="emerald-cover"', false);
        $response->assertSee('Buka');

        // The QR is an inline SVG built from the page's own canonical URL.
        $response->assertSee('<svg', false);
        $response->assertSee('shape-rendering="crispEdges"', false);
        $response->assertSee('aria-label="Kode QR undangan"', false);
    }

    public function test_the_sage_template_renders_both_columns_of_the_keepsake_sheet(): void
    {
        $this->seed(TemplateSeeder::class);

        $wedding = $this->publishWedding('sage-keepsake');

        $this->get(route('invitations.public', ['slug' => $wedding->slug]))
            ->assertOk()
            ->assertSee('Mohon Doa Restu')
            ->assertSee('Hormat kami')
            ->assertSee('Kami', false);
    }

    public function test_switching_the_draft_template_does_not_change_a_live_publication(): void
    {
        $this->seed(TemplateSeeder::class);

        $wedding = $this->publishWedding('classic-ivory');
        $newVersion = TemplateVersion::query()->where('renderer_key', 'modern-editorial.v1')->firstOrFail();

        $wedding->draft()->firstOrFail()->update(['template_version_id' => $newVersion->getKey()]);

        $this->get(route('invitations.public', ['slug' => $wedding->slug]))
            ->assertOk()
            ->assertSee('data-template="classic-ivory.v1"', false)
            ->assertDontSee('data-template="modern-editorial.v1"', false);
    }

    public function test_the_editor_lists_every_active_template(): void
    {
        $this->seed(TemplateSeeder::class);

        $owner = User::factory()->create();
        $version = TemplateVersion::query()->where('renderer_key', 'classic-ivory.v1')->firstOrFail();
        $wedding = app(CreateWedding::class)->handle($owner, 'Uji Template', $version);

        $response = $this->actingAs($owner)->get(route('weddings.edit', $wedding));

        $response->assertOk();

        // Nothing was ordered yet, so no design may be hidden from the owner;
        // the names come from the catalogue instead of a copy of it.
        foreach (InvitationTemplate::query()->orderBy('sort_order')->pluck('name') as $name) {
            $response->assertSee($name);
        }
    }

    private function publishWedding(string $slug): Wedding
    {
        $owner = User::factory()->create();
        $version = TemplateVersion::query()->where('renderer_key', $slug.'.v1')->firstOrFail();
        $wedding = app(CreateWedding::class)->handle($owner, 'Pernikahan '.$slug, $version);

        WeddingPartner::factory()->for($wedding)->create(['display_name' => 'Ayu Lestari']);
        WeddingPartner::factory()->for($wedding)->groom()->create(['display_name' => 'Bima Pratama']);
        WeddingEvent::factory()->for($wedding)->create();

        app(GrantInvitationEntitlement::class)->handle($wedding, $owner);
        app(PublishInvitation::class)->handle($wedding, $owner);

        return $wedding->refresh();
    }
}
