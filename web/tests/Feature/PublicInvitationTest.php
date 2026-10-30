<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\InvitationPublication;
use App\Models\Wedding;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PublicInvitationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TemplateSeeder::class);
    }

    public function test_an_unknown_slug_returns_not_found(): void
    {
        $this->get(route('invitations.public', ['slug' => 'tidak-ada']))->assertNotFound();
    }

    public function test_a_wedding_that_was_never_published_returns_not_found(): void
    {
        $wedding = Wedding::factory()->create();

        $this->get(route('invitations.public', ['slug' => $wedding->slug]))->assertNotFound();
    }

    public function test_a_published_invitation_is_served_from_the_snapshot(): void
    {
        $wedding = Wedding::factory()->published()->create(['internal_title' => 'Nama Internal Rahasia']);
        InvitationPublication::factory()->for($wedding)->create();

        $response = $this->get(route('invitations.public', ['slug' => $wedding->slug]));

        $response->assertOk()
            ->assertSee('Ayu Lestari')
            ->assertSee('Bima Pratama')
            ->assertDontSee('Nama Internal Rahasia');

        // Draft/relational changes after publication must not leak into the
        // public page until a new snapshot is published.
        $wedding->update(['internal_title' => 'Judul Baru Setelah Terbit']);

        $this->get(route('invitations.public', ['slug' => $wedding->slug]))
            ->assertOk()
            ->assertSee('Ayu Lestari')
            ->assertDontSee('Judul Baru Setelah Terbit');
    }

    public function test_an_expired_publication_returns_gone_without_data(): void
    {
        $wedding = Wedding::factory()->create();
        InvitationPublication::factory()->for($wedding)->expired()->create();

        $this->get(route('invitations.public', ['slug' => $wedding->slug]))
            ->assertStatus(410)
            ->assertSee('Masa aktif undangan telah berakhir')
            ->assertDontSee('Ayu Lestari');
    }

    public function test_an_unpublished_publication_returns_gone(): void
    {
        $wedding = Wedding::factory()->create();
        InvitationPublication::factory()->for($wedding)->unpublished()->create();

        $this->get(route('invitations.public', ['slug' => $wedding->slug]))
            ->assertStatus(410)
            ->assertSee('Undangan sedang tidak ditampilkan');
    }

    public function test_the_public_page_is_rate_limited_per_ip(): void
    {
        config(['platform.public.rate_limit_per_minute' => 2]);

        $wedding = Wedding::factory()->published()->create();
        InvitationPublication::factory()->for($wedding)->create();
        $url = route('invitations.public', ['slug' => $wedding->slug]);

        $this->get($url)->assertOk();
        $this->get($url)->assertOk();
        $this->get($url)->assertStatus(429);
    }
}
