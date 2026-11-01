<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Weddings\CreateWedding;
use App\Models\InvitationTemplate;
use App\Models\Plan;
use App\Models\ServiceEntitlement;
use App\Models\TemplateVersion;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingEvent;
use App\Models\WeddingPartner;
use App\Support\Billing\TemplateAccess;
use App\Support\Templates\TemplatePresentation;
use Database\Seeders\PlanSeeder;
use Database\Seeders\TemplateSeeder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The public theme catalogue (/tema) and the package ceiling that decides which
 * design an invitation may actually use. The catalogue keeps growing, so these
 * tests pin the browsing contract (filter, page, fragment) rather than a count.
 */
final class ThemeCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_catalogue_lists_every_published_theme_with_a_demo_link(): void
    {
        $this->seed(TemplateSeeder::class);

        $perPage = max(1, (int) config('platform.marketing.catalog_per_page', 9));
        $total = InvitationTemplate::query()->published()->count();
        $this->assertGreaterThan($perPage, $total, 'The catalogue must be paginated for this test to mean anything.');

        $first = $this->get(route('templates.index'))->assertOk();
        $first->assertSee('<link rel="canonical" href="'.route('templates.index').'">', false);
        $first->assertSee('"@type":"ItemList"', false);
        // Structured data announces the whole filtered catalogue, not just the page.
        $first->assertSee('"numberOfItems":'.$total, false);

        // Pages, not one big list: every theme has to be reachable by walking the
        // pagination, which is what a crawler and the load-more control both do.
        $seen = '';

        for ($page = 1; $page <= (int) ceil($total / $perPage); $page++) {
            $seen .= $this->get(route('templates.index', ['page' => $page]))->assertOk()->content();
        }

        foreach (InvitationTemplate::query()->published()->get() as $template) {
            $this->assertStringContainsString($template->name, $seen);
            $this->assertStringContainsString(route('templates.demo', ['template' => $template->slug]), $seen);
        }
    }

    public function test_an_archived_theme_never_reaches_the_catalogue(): void
    {
        $this->seed(TemplateSeeder::class);

        InvitationTemplate::query()->where('slug', 'classic-ivory')->update([
            'status' => InvitationTemplate::STATUS_ARCHIVED,
        ]);

        $this->get(route('templates.index'))
            ->assertOk()
            ->assertDontSee('Classic Ivory')
            ->assertDontSee(route('templates.demo', ['template' => 'classic-ivory']), false);
    }

    public function test_the_catalogue_filters_by_category_and_package(): void
    {
        $this->seed(TemplateSeeder::class);

        $luxury = InvitationTemplate::query()->published()->where('category', 'luxury')->pluck('name');
        $premium = InvitationTemplate::query()->published()->where('tier', InvitationTemplate::TIER_PREMIUM)->pluck('name');

        $byCategory = $this->get(route('templates.index', ['kategori' => 'luxury']))->assertOk();
        foreach ($luxury as $name) {
            $byCategory->assertSee($name);
        }
        $byCategory->assertDontSee(
            InvitationTemplate::query()->published()->where('category', '!=', 'luxury')->value('name')
        );

        $byTier = $this->get(route('templates.index', ['paket' => InvitationTemplate::TIER_PREMIUM]))->assertOk();
        foreach ($premium as $name) {
            $byTier->assertSee($name);
        }
        $byTier->assertDontSee(
            InvitationTemplate::query()->published()->where('tier', '!=', InvitationTemplate::TIER_PREMIUM)->value('name')
        );

        // Both filters compose, so a visitor can ask for one narrow thing.
        $this->get(route('templates.index', ['kategori' => 'luxury', 'paket' => InvitationTemplate::TIER_PREMIUM]))
            ->assertOk()
            ->assertSee($premium->random());
    }

    public function test_an_unknown_filter_value_narrows_nothing_instead_of_failing(): void
    {
        $this->seed(TemplateSeeder::class);

        $total = InvitationTemplate::query()->published()->count();

        $response = $this->get(route('templates.index', ['kategori' => 'tidak-ada', 'paket' => 'diamond']))->assertOk();

        $response->assertSee('dari '.$total.' tema', false);
    }

    public function test_an_empty_combination_points_back_at_the_whole_catalogue(): void
    {
        $this->seed(TemplateSeeder::class);

        $this->get(route('templates.index', ['kategori' => 'minimal', 'paket' => 'premium']))
            ->assertOk()
            ->assertSee('Belum ada tema pada kombinasi pilihan ini.')
            ->assertSee(route('templates.index'), false)
            ->assertDontSee('data-catalog-grid', false);
    }

    public function test_a_catalogue_without_any_published_theme_does_not_blame_the_visitor(): void
    {
        // Nothing is seeded: the empty page has to admit the catalogue is empty
        // instead of suggesting the visitor picked a bad combination, and it must
        // not offer a link that reloads the very same empty page.
        $this->get(route('templates.index'))
            ->assertOk()
            ->assertSee('Belum ada tema yang dapat ditampilkan saat ini.')
            ->assertDontSee('Belum ada tema pada kombinasi pilihan ini.')
            ->assertSee(route('home').'#contoh', false)
            ->assertDontSee('data-catalog-grid', false);

        // A visitor who still arrives with a filter keeps the combination wording,
        // because there the other chips really are the way out.
        $this->get(route('templates.index', ['kategori' => 'classic']))
            ->assertOk()
            ->assertSee('Belum ada tema pada kombinasi pilihan ini.')
            ->assertSee(route('templates.index'), false);
    }

    public function test_the_catalogue_paginates_and_offers_the_next_page_as_a_fragment(): void
    {
        $this->seed(TemplateSeeder::class);
        config(['platform.marketing.catalog_per_page' => 4]);

        $total = InvitationTemplate::query()->published()->count();
        $this->assertGreaterThan(4, $total);

        $first = $this->get(route('templates.index'))->assertOk();
        $first->assertSee('data-catalog-shown>4</span>', false);
        $first->assertSee('dari '.$total.' tema', false);
        $first->assertSee('data-load-more', false);

        // The plain href works with JavaScript switched off, and the enhanced
        // control reads the same page as an appendable fragment.
        $second = route('templates.index', ['page' => 2]);
        $fragment = route('templates.index', ['page' => 2, 'bagian' => 'kartu']);
        $first->assertSee('href="'.$second.'"', false);
        $first->assertSee('data-load-more-url="'.e($fragment).'"', false);

        $this->get($fragment)
            ->assertOk()
            ->assertSee('data-catalog-grid', false)
            ->assertDontSee('<!DOCTYPE', false)
            ->assertDontSee('Katalog Tema');

        // Page two keeps the cards the first page already showed out of the way.
        $this->get(route('templates.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('data-catalog-shown', false);
    }

    public function test_the_last_page_stops_offering_more_themes(): void
    {
        $this->seed(TemplateSeeder::class);
        config(['platform.marketing.catalog_per_page' => 4]);

        $total = InvitationTemplate::query()->published()->count();
        $lastPage = (int) ceil($total / 4);

        $this->get(route('templates.index', ['page' => $lastPage]))
            ->assertOk()
            ->assertDontSee('data-load-more', false)
            ->assertSee('dari '.$total.' tema', false);
    }

    public function test_the_sitemap_publishes_the_catalogue_and_every_demo(): void
    {
        $this->seed(TemplateSeeder::class);

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertSee('<loc>'.route('templates.index').'</loc>', false)
            ->assertSee('<loc>'.route('templates.demo', ['template' => 'deco-noir']).'</loc>', false);
    }

    public function test_a_card_names_the_package_that_unlocks_an_exclusive_theme(): void
    {
        $this->seed([TemplateSeeder::class, PlanSeeder::class]);

        // The badge is what tells a shopper that a design is not free to take, so
        // it has to be derived from the package catalogue instead of written twice.
        $templates = InvitationTemplate::query()->published()->orderBy('sort_order')->get();
        $plans = Plan::query()->active()->with('features')->orderBy('price_amount')->get();

        $notes = app(TemplateAccess::class)->unlockNotes($templates, $plans);

        // A design every package already includes gets no note: "available in all
        // packages" is not a reason to interrupt the customer.
        $this->assertArrayNotHasKey('classic-ivory', $notes);
        $this->assertSame('Paket Signature ke atas', $notes['sage-keepsake']);
        $this->assertSame('Paket Premium ke atas', $notes['velvet-royal']);

        foreach ($notes as $slug => $note) {
            $this->assertContains(
                InvitationTemplate::query()->where('slug', $slug)->value('tier'),
                [InvitationTemplate::TIER_SIGNATURE, InvitationTemplate::TIER_PREMIUM],
                $slug.' is advertised as needing a package it does not.',
            );
            $this->assertStringContainsString('ke atas', $note);
        }

        $page = $this->get(route('templates.index'))->assertOk();

        foreach ($notes as $note) {
            $page->assertSee($note);
        }
    }

    public function test_the_creation_form_offers_every_theme_because_nothing_is_bought_yet(): void
    {
        $this->seed(TemplateSeeder::class);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('weddings.create'))
            ->assertOk();

        foreach (InvitationTemplate::query()->published()->pluck('name') as $name) {
            $response->assertSee($name);
        }

        // The tier wording is shown, but nothing is refused: the package is
        // picked after the invitation is written.
        $response->assertDontSee('baru bisa dipilih setelah paket');
    }

    public function test_the_editor_disables_themes_the_paid_package_does_not_unlock(): void
    {
        $this->seed(TemplateSeeder::class);

        [$owner, $wedding] = $this->weddingWithCeiling(InvitationTemplate::TIER_ESSENTIAL);

        $signature = InvitationTemplate::query()
            ->where('tier', InvitationTemplate::TIER_SIGNATURE)
            ->firstOrFail();

        $response = $this->actingAs($owner)
            ->get(route('weddings.edit', $wedding))
            ->assertOk();

        $response->assertSee('Eksklusif paket Premium')
            ->assertSee('baru bisa dipilih setelah paket');

        // The locked option names what it needs, so the customer can see what to
        // buy instead of guessing from a greyed-out design.
        $response->assertSee(
            $signature->name.' (versi 1) — '.TemplatePresentation::tierLabel($signature->tier),
            false,
        );
    }

    public function test_a_draft_cannot_be_switched_onto_a_theme_the_package_does_not_unlock(): void
    {
        $this->seed(TemplateSeeder::class);

        [$owner, $wedding] = $this->weddingWithCeiling(InvitationTemplate::TIER_ESSENTIAL);

        $premium = TemplateVersion::query()
            ->whereHas('template', static fn (Builder $query): Builder => $query->where('tier', InvitationTemplate::TIER_PREMIUM))
            ->where('status', 'published')
            ->firstOrFail();

        $this->actingAs($owner)
            ->putJson(route('weddings.draft.update', $wedding), [
                'template_version_id' => $premium->getKey(),
                'content' => CreateWedding::defaultContent(),
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['message']);

        $this->assertNotSame($premium->getKey(), $wedding->draft()->firstOrFail()->template_version_id);
    }

    public function test_a_draft_keeps_saving_on_the_theme_it_was_built_with(): void
    {
        $this->seed(TemplateSeeder::class);

        // The theme was chosen while the choice was free. Locking the editor now
        // would strand the owner over a billing decision, so only a *switch* is
        // refused and publication is where the ceiling is enforced.
        $premiumVersion = TemplateVersion::query()
            ->whereHas('template', static fn (Builder $query): Builder => $query->where('tier', InvitationTemplate::TIER_PREMIUM))
            ->where('status', 'published')
            ->firstOrFail();

        $owner = User::factory()->create();
        $wedding = app(CreateWedding::class)->handle($owner, 'Velvet Draf', $premiumVersion);

        ServiceEntitlement::factory()->create([
            'wedding_id' => $wedding->getKey(),
            'limits_json' => [
                'months' => 12,
                'source' => 'order',
                'features' => ['template_tier' => ['tier' => InvitationTemplate::TIER_ESSENTIAL]],
            ],
        ]);

        $content = CreateWedding::defaultContent();
        $content['cover']['title'] = 'Tetap Bisa Disimpan';

        $this->actingAs($owner)
            ->putJson(route('weddings.draft.update', $wedding), [
                'template_version_id' => $premiumVersion->getKey(),
                'content' => $content,
            ])
            ->assertOk();

        $this->assertSame('Tetap Bisa Disimpan', $wedding->draft()->firstOrFail()->content_json['cover']['title']);
    }

    public function test_an_upgrade_unlocks_the_theme_without_touching_the_draft(): void
    {
        $this->seed(TemplateSeeder::class);

        [$owner, $wedding] = $this->weddingWithCeiling(InvitationTemplate::TIER_SIGNATURE);

        $signature = TemplateVersion::query()
            ->whereHas('template', static fn (Builder $query): Builder => $query->where('tier', InvitationTemplate::TIER_SIGNATURE))
            ->where('status', 'published')
            ->firstOrFail();

        $this->actingAs($owner)
            ->putJson(route('weddings.draft.update', $wedding), [
                'template_version_id' => $signature->getKey(),
                'content' => CreateWedding::defaultContent(),
            ])
            ->assertOk();

        $this->assertSame($signature->getKey(), $wedding->draft()->firstOrFail()->template_version_id);
    }

    public function test_publishing_a_theme_the_package_does_not_cover_is_refused(): void
    {
        $this->seed(TemplateSeeder::class);

        $premiumVersion = TemplateVersion::query()
            ->whereHas('template', static fn (Builder $query): Builder => $query->where('tier', InvitationTemplate::TIER_PREMIUM))
            ->where('status', 'published')
            ->firstOrFail();

        $owner = User::factory()->create();
        $wedding = app(CreateWedding::class)->handle($owner, 'Terbit Premium', $premiumVersion);

        WeddingPartner::factory()->for($wedding)->create(['display_name' => 'Ayu Lestari']);
        WeddingPartner::factory()->for($wedding)->groom()->create(['display_name' => 'Bima Pratama']);
        WeddingEvent::factory()->for($wedding)->create();

        ServiceEntitlement::factory()->create([
            'wedding_id' => $wedding->getKey(),
            'limits_json' => [
                'months' => 12,
                'source' => 'order',
                'features' => ['template_tier' => ['tier' => InvitationTemplate::TIER_ESSENTIAL]],
            ],
        ]);

        $this->actingAs($owner)
            ->post(route('weddings.publish', $wedding))
            ->assertSessionHasErrors('publish');

        $this->assertDatabaseCount('invitation_publications', 0);
    }

    public function test_a_manual_support_grant_does_not_lock_a_paying_customer_out(): void
    {
        $this->seed(TemplateSeeder::class);

        // A grant with no frozen plan features predates the tier catalogue. It has
        // to read as the top tier, or every existing support grant would suddenly
        // lose the design it was given.
        $wedding = Wedding::factory()->create();

        ServiceEntitlement::factory()->create([
            'wedding_id' => $wedding->getKey(),
            'limits_json' => ['months' => 12, 'source' => 'manual_grant'],
        ]);

        $premium = InvitationTemplate::query()->where('tier', InvitationTemplate::TIER_PREMIUM)->firstOrFail();
        $access = app(TemplateAccess::class);

        $this->assertTrue($access->allows($access->ceilingFor($wedding), $premium));
    }

    /**
     * @return array{0: User, 1: Wedding}
     */
    private function weddingWithCeiling(string $tier): array
    {
        $owner = User::factory()->create();
        $entryVersion = TemplateVersion::query()
            ->whereHas('template', static fn (Builder $query): Builder => $query->where('tier', InvitationTemplate::TIER_ESSENTIAL))
            ->where('status', 'published')
            ->firstOrFail();
        $wedding = app(CreateWedding::class)->handle($owner, 'Undangan Tier '.$tier, $entryVersion);

        ServiceEntitlement::factory()->create([
            'wedding_id' => $wedding->getKey(),
            'limits_json' => [
                'months' => 12,
                'source' => 'order',
                'features' => ['template_tier' => ['tier' => $tier]],
            ],
        ]);

        return [$owner, $wedding];
    }
}
