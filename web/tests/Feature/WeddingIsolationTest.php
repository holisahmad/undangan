<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Weddings\CreateWedding;
use App\Models\TemplateVersion;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingPartner;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WeddingIsolationTest extends TestCase
{
    use RefreshDatabase;

    private TemplateVersion $templateVersion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TemplateSeeder::class);
        $this->templateVersion = TemplateVersion::query()->firstOrFail();
    }

    public function test_the_owner_can_view_and_edit_their_wedding(): void
    {
        $owner = User::factory()->create();
        $wedding = $this->createWedding($owner);

        $this->actingAs($owner)->get(route('weddings.show', $wedding))->assertOk();
        $this->actingAs($owner)->get(route('weddings.edit', $wedding))->assertOk();
    }

    public function test_another_customer_cannot_reach_the_wedding_even_with_the_uuid(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $wedding = $this->createWedding($owner);

        $this->actingAs($intruder)->get(route('weddings.show', $wedding))->assertForbidden();
        $this->actingAs($intruder)->get(route('weddings.edit', $wedding))->assertForbidden();
        $this->actingAs($intruder)
            ->patch(route('weddings.update', $wedding), ['internal_title' => 'Diambil alih'])
            ->assertForbidden();

        $this->assertDatabaseHas('weddings', [
            'id' => $wedding->getKey(),
            'internal_title' => $wedding->internal_title,
        ]);
    }

    public function test_the_index_only_lists_the_customers_own_weddings(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $own = $this->createWedding($owner, 'Pernikahan Milik Sendiri');
        $this->createWedding($other, 'Pernikahan Orang Lain');

        $this->actingAs($owner)
            ->get(route('weddings.index'))
            ->assertOk()
            ->assertSee($own->internal_title)
            ->assertDontSee('Pernikahan Orang Lain');
    }

    public function test_a_partner_from_another_wedding_cannot_be_modified(): void
    {
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();
        $weddingA = $this->createWedding($ownerA);
        $weddingB = $this->createWedding($ownerB);

        $foreignPartner = WeddingPartner::factory()->for($weddingB)->create();

        $this->actingAs($ownerA)
            ->patch(route('weddings.partners.update', [$weddingA, $foreignPartner]), [
                'partner_type' => 'bride',
                'display_name' => 'Diubah',
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('wedding_partners', ['id' => $foreignPartner->getKey(), 'display_name' => 'Diubah']);
    }

    private function createWedding(User $owner, ?string $title = null): Wedding
    {
        return app(CreateWedding::class)->handle(
            $owner,
            $title ?? 'Pernikahan '.$owner->name,
            $this->templateVersion,
        );
    }
}
