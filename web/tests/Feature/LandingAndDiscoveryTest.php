<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\InvitationTemplate;
use App\Models\User;
use App\Support\Contact\PhoneNumber;
use Database\Seeders\PlanSeeder;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LandingAndDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_landing_page_carries_metadata_and_structured_data(): void
    {
        $this->seed(TemplateSeeder::class);

        $response = $this->get(route('home'))->assertOk();

        $response->assertSee('<meta name="description" content="'.e(config('site.description')).'">', false);
        $response->assertSee('<link rel="canonical" href="'.route('home').'">', false);
        $response->assertSee('<meta property="og:title"', false);
        $response->assertSee('application/ld+json', false);
        $response->assertSee('"@context":"https://schema.org"', false);
        $response->assertSee('"@type":"Organization"', false);
        $response->assertSee('"@type":"ItemList"', false);
    }

    public function test_the_landing_page_carries_a_curated_strip_that_hands_over_to_the_catalogue(): void
    {
        $this->seed(TemplateSeeder::class);

        $limit = max(1, (int) config('platform.marketing.landing_template_limit', 6));
        $total = InvitationTemplate::query()->published()->count();
        $this->assertGreaterThan($limit, $total, 'The catalogue has to outgrow the strip for this to mean anything.');

        $ordered = InvitationTemplate::query()
            ->published()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name')
            ->values();
        $strip = $ordered->take($limit);
        $later = $ordered->slice($limit);
        $this->assertCount($limit, $strip);
        $this->assertNotEmpty($later);

        $response = $this->get(route('home'))->assertOk();

        foreach ($strip as $name) {
            $response->assertSee($name);
        }

        // The themes that no longer fit are not crammed onto the landing: /tema owns
        // the filtered, paginated view and the strip is the doorway to it.
        foreach ($later as $name) {
            $response->assertDontSee($name);
        }

        $response->assertSee('Lihat contoh')
            ->assertSee('Lihat semua '.$total.' tema')
            ->assertSee(route('templates.index'), false);
    }

    public function test_the_landing_page_presents_wedding_services_as_partner_work(): void
    {
        $services = config('site.services');
        $this->assertNotEmpty($services);

        $response = $this->get(route('home'))->assertOk();

        $response->assertSee('id="layanan"', false);

        foreach ($services as $service) {
            $response->assertSee($service['name']);
            $response->assertSee($service['provider']);
            // Each card has to arrive at the form with the interest already set,
            // otherwise the section is decoration rather than a sales path.
            $response->assertSee('layanan='.$service['slug'], false);
        }

        $response->assertSee('name="service_interest"', false);

        // The platform does not perform these services, and says so where the
        // promise is made.
        $response->assertSee('dikonfirmasi langsung oleh mitranya');
    }

    public function test_the_services_section_disappears_when_no_partner_is_configured(): void
    {
        config(['site.services' => []]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('id="layanan"', false)
            ->assertSee('id="kontak"', false);
    }

    public function test_the_landing_page_renders_before_any_template_is_seeded(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Belum ada template yang terbit.');
    }

    public function test_the_sitemap_lists_public_pages_and_hides_private_ones(): void
    {
        $this->seed(TemplateSeeder::class);

        $response = $this->get(route('sitemap'))->assertOk();

        $this->assertStringContainsString('application/xml', (string) $response->headers->get('Content-Type'));

        $response->assertSee('<loc>'.route('home').'</loc>', false);
        $response->assertSee('<loc>'.route('register').'</loc>', false);
        $response->assertSee('<loc>'.route('login').'</loc>', false);
        $response->assertSee('<loc>'.route('templates.demo', ['template' => 'classic-ivory']).'</loc>', false);
        $response->assertSee('<loc>'.route('legal.privacy').'</loc>', false);
        $response->assertSee('<loc>'.route('legal.terms').'</loc>', false);

        preg_match_all('#<loc>(.*?)</loc>#', (string) $response->getContent(), $matches);
        $this->assertNotEmpty($matches[1]);

        foreach ($matches[1] as $loc) {
            $path = (string) parse_url($loc, PHP_URL_PATH);

            foreach (['/admin', '/dashboard', '/weddings', '/orders', '/checkout', '/g/', '/u/', '/media/'] as $privatePath) {
                $this->assertStringStartsNotWith($privatePath, $path, 'Private path listed in sitemap: '.$loc);
            }
        }
    }

    public function test_robots_txt_disallows_private_areas_and_advertises_the_sitemap(): void
    {
        $this->get(route('robots'))
            ->assertOk()
            ->assertSee('User-agent: *')
            ->assertSee('Allow: /')
            ->assertSee('Disallow: /admin')
            ->assertSee('Disallow: /g/')
            ->assertSee('Sitemap: '.route('sitemap'));

        // The dynamic route replaces the static file, which could not carry a
        // host-specific sitemap URL.
        $this->assertFileDoesNotExist(public_path('robots.txt'));
    }

    public function test_the_landing_page_prices_every_active_plan_from_the_catalogue(): void
    {
        $this->seed([TemplateSeeder::class, PlanSeeder::class]);

        $response = $this->get(route('home'))->assertOk();

        $response->assertSee('id="harga"', false);
        $response->assertSee('Essential');
        $response->assertSee('Signature');
        $response->assertSee('Premium');
        $response->assertSee('Rp99.000');
        $response->assertSee('Rp199.000');
        $response->assertSee('Rp349.000');
        $response->assertSee('Masa aktif 12 bulan');
        $response->assertSee('Kuota tamu');
        $response->assertSee('300 tamu');
        $response->assertSee('Tanpa batas');
    }

    public function test_the_pricing_section_degrades_gracefully_without_plans(): void
    {
        $this->seed(TemplateSeeder::class);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Daftar harga sedang diperbarui.')
            ->assertDontSee('Rp99.000');
    }

    public function test_the_landing_page_answers_the_faq_and_marks_it_up(): void
    {
        $faqs = config('site.faq');

        $response = $this->get(route('home'))->assertOk();

        $response->assertSee('id="faq"', false);
        $response->assertSee('Pertanyaan umum');
        $response->assertSee('"@type":"FAQPage"', false);

        foreach ($faqs as $faq) {
            $response->assertSee($faq['question']);
        }
    }

    public function test_the_contact_section_points_at_the_configured_address(): void
    {
        config([
            'site.contact.email' => 'halo@example.test',
            'site.contact.whatsapp' => '+62 812-3456-7890',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('id="kontak"', false)
            ->assertSee('mailto:halo@example.test', false)
            ->assertSee('https://wa.me/6281234567890', false);
    }

    public function test_the_studio_details_are_published_where_a_customer_looks(): void
    {
        $address = config('site.address');
        $contact = config('site.contact');
        $phone = PhoneNumber::e164((string) $contact['phone']);
        $waDigits = PhoneNumber::international((string) $contact['phone']);

        $home = $this->get(route('home'))->assertOk();

        $home->assertSee('id="kontak"', false)
            ->assertSee(config('site.operator'))
            ->assertSee($address['street'])
            ->assertSee('href="'.$contact['maps_url'].'"', false)
            ->assertSee('href="tel:'.$phone.'"', false)
            ->assertSee('href="https://wa.me/'.$waDigits.'"', false);

        // The studio is the entity a customer contracts with, so it is published
        // as the organisation's legal name with a reachable address.
        $home->assertSee('"legalName":"'.config('site.operator').'"', false)
            ->assertSee('"telephone":"'.$phone.'"', false)
            ->assertSee('"streetAddress":"'.$address['street'].'"', false)
            ->assertSee('"postalCode":"'.$address['postal_code'].'"', false)
            ->assertSee('"hasMap":"'.$contact['maps_url'].'"', false);

        foreach (['legal.privacy', 'legal.terms'] as $name) {
            $this->get(route($name))
                ->assertOk()
                ->assertSee(config('site.operator'))
                ->assertSee($address['street'])
                ->assertSee($contact['maps_url'], false);
        }
    }

    public function test_the_legal_pages_are_public_and_link_to_the_contact_address(): void
    {
        foreach (['legal.privacy' => 'Kebijakan privasi', 'legal.terms' => 'Syarat &amp; ketentuan'] as $name => $heading) {
            $this->get(route($name))
                ->assertOk()
                ->assertSee($heading, false)
                ->assertSee('<link rel="canonical" href="'.route($name).'">', false)
                ->assertSee('mailto:'.config('site.contact.email'), false);
        }

        $this->get(route('legal.terms'))
            ->assertSee('Hukum yang berlaku')
            ->assertSee('Republik Indonesia');
    }

    public function test_the_landing_page_links_the_legal_pages(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('legal.privacy'), false)
            ->assertSee(route('legal.terms'), false);
    }

    public function test_every_template_has_an_indexable_demo_page(): void
    {
        $this->seed(TemplateSeeder::class);

        foreach (InvitationTemplate::query()->pluck('slug') as $slug) {
            $this->get(route('templates.demo', ['template' => $slug]))
                ->assertOk()
                ->assertSee('data-template="'.$slug.'.v1"', false)
                ->assertSee('Ayu Lestari')
                ->assertSee('Bima Pratama')
                ->assertSee('Buat undangan seperti ini')
                ->assertSee('<meta name="robots" content="index,follow">', false)
                ->assertDontSee('<meta name="robots" content="noindex,nofollow">', false)
                ->assertDontSee('Konfirmasi kehadiran');
        }
    }

    public function test_a_demo_page_is_not_available_for_an_unpublished_template(): void
    {
        $this->seed(TemplateSeeder::class);

        $this->get(route('templates.demo', ['template' => 'tidak-ada']))->assertNotFound();

        InvitationTemplate::query()->where('slug', 'classic-ivory')->update([
            'status' => InvitationTemplate::STATUS_ARCHIVED,
        ]);

        $this->get(route('templates.demo', ['template' => 'classic-ivory']))->assertNotFound();
    }

    public function test_the_creation_form_links_each_template_to_its_demo(): void
    {
        $this->seed(TemplateSeeder::class);

        $this->actingAs(User::factory()->create())
            ->get(route('weddings.create'))
            ->assertOk()
            ->assertSee('Lihat contoh')
            ->assertSee(route('templates.demo', ['template' => 'modern-editorial']), false);
    }
}
