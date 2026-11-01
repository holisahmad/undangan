<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\InvitationPublication;
use App\Models\Rsvp;
use App\Models\Wedding;
use App\Support\Invitations\InvitationSnapshot;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The shared "power kit" that every template includes: countdown, calendar
 * download, gift accounts, the consent-gated wish wall and the section nav.
 *
 * These blocks are rendered by one set of partials, so they are tested once
 * here instead of nine times per template.
 */
final class InvitationPowerKitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TemplateSeeder::class);
    }

    public function test_the_countdown_and_calendar_shortcuts_follow_the_published_event(): void
    {
        $wedding = $this->publishWithFutureReception();

        $response = $this->get(route('invitations.public', ['slug' => $wedding->slug]))->assertOk();

        $response->assertSee('data-countdown', false);
        $response->assertSee('data-unit="days"', false);
        $response->assertSee('Menghitung Hari');
        $response->assertSee('Simpan tanggal');
        $response->assertSee(route('invitations.calendar', ['slug' => $wedding->slug]), false);
        $response->assertSee('calendar.google.com', false);
    }

    public function test_the_calendar_file_describes_the_published_event(): void
    {
        $wedding = $this->publishWithFutureReception();

        $response = $this->get(route('invitations.calendar', ['slug' => $wedding->slug]))->assertOk();

        $this->assertStringContainsString('text/calendar', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('.ics', (string) $response->headers->get('Content-Disposition'));

        $response->assertSee('BEGIN:VCALENDAR', false);
        $response->assertSee('SUMMARY:Resepsi', false);
        $response->assertSee('Pendopo Kayu', false);
    }

    public function test_the_demo_template_offers_its_own_calendar_file(): void
    {
        $this->get(route('templates.calendar', ['template' => 'glasshouse-minimal']))
            ->assertOk()
            ->assertSee('BEGIN:VCALENDAR', false)
            ->assertSee('DTSTART:', false);
    }

    public function test_gift_accounts_stay_behind_a_reveal_toggle(): void
    {
        $wedding = $this->publishWithFutureReception();

        $response = $this->get(route('invitations.public', ['slug' => $wedding->slug]))
            ->assertOk()
            ->assertSee('id="hadiah"', false)
            ->assertSee('Buka rekening hadiah')
            ->assertSee('Sembunyikan rekening')
            ->assertSee('BCA')
            ->assertSee('data-copy="1234567890"', false)
            ->assertSee('a.n. Ayu Lestari');

        // A guest has to ask for the number, so the block ships closed and the
        // number is never printed outside it. It is still plain HTML rather than
        // something a script injects, which keeps the toggle working without
        // JavaScript.
        $html = $response->content();
        $reveal = strpos($html, 'data-gift-reveal');
        $number = strpos($html, '1234567890');

        $this->assertNotFalse($reveal, 'The gift block needs its reveal toggle.');
        $this->assertStringNotContainsString('data-gift-reveal open', $html);
        $this->assertGreaterThan($reveal, $number, 'The account number must sit inside the reveal.');
        $this->assertLessThan(strpos($html, '</details>', $reveal), $number);
    }

    public function test_the_gift_block_disappears_when_switched_off_or_left_empty(): void
    {
        $off = $this->publishWithFutureReception(['features' => [
            'show_story' => true,
            'show_events' => true,
            'show_map' => true,
            'show_gift' => false,
        ]]);

        $this->get(route('invitations.public', ['slug' => $off->slug]))
            ->assertOk()
            ->assertDontSee('id="hadiah"', false)
            ->assertDontSee('1234567890');

        $empty = $this->publishWithFutureReception(['gift' => [
            'enabled' => true,
            'note' => null,
            'address' => null,
            'accounts' => [['bank' => 'BCA', 'number' => '  ', 'holder' => 'Ayu Lestari']],
        ]]);

        $this->get(route('invitations.public', ['slug' => $empty->slug]))
            ->assertOk()
            ->assertDontSee('id="hadiah"', false);
    }

    public function test_the_wish_wall_only_lists_guests_who_consented(): void
    {
        $wedding = $this->publishWithFutureReception();

        $consenting = Guest::factory()->for($wedding)->create(['display_name' => 'Rani']);
        Rsvp::factory()->for($consenting)->create([
            'message' => 'Selamat menempuh hidup baru, semoga sakinah.',
            'show_message' => true,
        ]);

        $private = Guest::factory()->for($wedding)->create(['display_name' => 'Tamu Tertutup']);
        Rsvp::factory()->for($private)->create([
            'message' => 'Pesan yang tidak ingin dibagikan.',
            'show_message' => false,
        ]);

        $this->get(route('invitations.public', ['slug' => $wedding->slug]))
            ->assertOk()
            ->assertSee('id="ucapan"', false)
            ->assertSee('Rani')
            ->assertSee('Selamat menempuh hidup baru, semoga sakinah.')
            ->assertDontSee('Pesan yang tidak ingin dibagikan.')
            ->assertDontSee('Tamu Tertutup');
    }

    public function test_the_wish_wall_pages_through_long_lists(): void
    {
        $wedding = $this->publishWithFutureReception();

        for ($index = 1; $index <= 8; $index++) {
            $guest = Guest::factory()->for($wedding)->create(['display_name' => 'Tamu '.$index]);
            Rsvp::factory()->for($guest)->create([
                'message' => 'Ucapan nomor '.$index.' untuk mempelai.',
                'show_message' => true,
                // Distinct timestamps keep the newest-first order deterministic.
                'submitted_at' => now()->subMinutes($index),
            ]);
        }

        $this->get(route('invitations.public', ['slug' => $wedding->slug]))
            ->assertOk()
            ->assertSee('Halaman ucapan')
            ->assertSee('ucapan=2', false);

        // Page two holds the two oldest messages, not a repeat of the first six.
        $this->get(route('invitations.public', ['slug' => $wedding->slug, 'ucapan' => 2]))
            ->assertOk()
            ->assertSee('Ucapan nomor 8 untuk mempelai.')
            ->assertDontSee('Ucapan nomor 1 untuk mempelai.');
    }

    public function test_the_section_nav_only_links_sections_that_exist(): void
    {
        $wedding = $this->publishWithFutureReception(['gift' => [
            'enabled' => false,
            'note' => null,
            'address' => null,
            'accounts' => [],
        ]]);

        $this->get(route('invitations.public', ['slug' => $wedding->slug]))
            ->assertOk()
            ->assertSee('aria-label="Bagian undangan"', false)
            ->assertSee('href="#mempelai"', false)
            ->assertSee('href="#acara"', false)
            ->assertDontSee('href="#hadiah"', false)
            ->assertDontSee('href="#ucapan"', false);
    }

    public function test_the_demo_pages_never_read_a_real_wedding_wish(): void
    {
        $wedding = $this->publishWithFutureReception();

        $guest = Guest::factory()->for($wedding)->create(['display_name' => 'Rani']);
        Rsvp::factory()->for($guest)->create([
            'message' => 'Pesan asli dari tamu sungguhan.',
            'show_message' => true,
        ]);

        $this->get(route('templates.demo', ['template' => 'glasshouse-minimal']))
            ->assertOk()
            ->assertSee('Rani')
            ->assertDontSee('Pesan asli dari tamu sungguhan.');
    }

    /**
     * Publishes a wedding on the Glasshouse template with one future reception
     * and the sample gift block, so every power-kit block has data to render.
     *
     * @param  array<string, mixed>  $content
     */
    private function publishWithFutureReception(array $content = []): Wedding
    {
        $wedding = Wedding::factory()->published()->create();

        $starts = now()->addDays(10)->setTime(9, 0);

        InvitationPublication::factory()->for($wedding)->create([
            'published_snapshot' => [
                'format_version' => InvitationSnapshot::FORMAT_VERSION,
                'template' => [
                    'slug' => 'glasshouse-minimal',
                    'name' => 'Glasshouse Minimal',
                    'version' => 1,
                    'renderer_key' => 'glasshouse-minimal.v1',
                    'config_schema_version' => '2',
                ],
                'couple' => [
                    [
                        'type' => 'bride',
                        'display_name' => 'Ayu Lestari',
                        'nickname' => 'Ayu',
                        'parents' => null,
                        'biography' => null,
                        'instagram' => null,
                    ],
                    [
                        'type' => 'groom',
                        'display_name' => 'Bima Pratama',
                        'nickname' => 'Bima',
                        'parents' => null,
                        'biography' => null,
                        'instagram' => null,
                    ],
                ],
                'events' => [[
                    'type' => 'reception',
                    'title' => 'Resepsi',
                    'starts_at' => $starts->toIso8601String(),
                    'ends_at' => $starts->copy()->addHours(4)->toIso8601String(),
                    'starts_label' => $starts->copy()->locale('id')->translatedFormat('l, d F Y · H:i'),
                    'ends_label' => $starts->copy()->addHours(4)->format('H:i'),
                    'timezone' => 'Asia/Jakarta',
                    'venue_name' => 'Pendopo Kayu',
                    'address' => 'Jl. Melati 8, Yogyakarta',
                    'map_url' => null,
                ]],
                'content' => array_replace($this->defaultContent(), $content),
                'published_at' => now()->toIso8601String(),
                'expires_at' => null,
            ],
        ]);

        return $wedding;
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultContent(): array
    {
        return [
            'cover' => [
                'title' => 'The Wedding Of',
                'greeting' => null,
                'quote' => null,
                'quote_source' => null,
            ],
            'story' => null,
            'features' => [
                'show_story' => true,
                'show_events' => true,
                'show_map' => true,
                'show_gift' => true,
            ],
            'gift' => [
                'enabled' => true,
                'note' => 'Terima kasih atas doa restunya.',
                'address' => null,
                'accounts' => [[
                    'bank' => 'BCA',
                    'number' => '1234567890',
                    'holder' => 'Ayu Lestari',
                ]],
            ],
        ];
    }
}
