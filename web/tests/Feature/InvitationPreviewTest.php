<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Weddings\CreateWedding;
use App\Models\TemplateVersion;
use App\Models\User;
use App\Models\Wedding;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class InvitationPreviewTest extends TestCase
{
    use RefreshDatabase;

    private TemplateVersion $templateVersion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TemplateSeeder::class);
        $this->templateVersion = TemplateVersion::query()->firstOrFail();
    }

    public function test_guests_cannot_preview_a_draft(): void
    {
        $wedding = $this->createWedding(User::factory()->create());

        $this->get(route('weddings.preview', $wedding))->assertRedirect(route('login'));
    }

    public function test_another_customer_cannot_preview_someone_elses_draft(): void
    {
        $wedding = $this->createWedding(User::factory()->create());
        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->get(route('weddings.preview', $wedding))
            ->assertForbidden();
    }

    public function test_the_owner_sees_unpublished_draft_content_marked_as_preview(): void
    {
        $owner = User::factory()->create();
        $wedding = $this->createWedding($owner);

        $content = CreateWedding::defaultContent();
        $content['cover']['title'] = 'Draf Rahasia Ayu';
        $content['story'] = ['heading' => 'Awal Kami', 'body' => 'Bertemu di kampus.'];
        $wedding->draft()->firstOrFail()->update(['content_json' => $content]);

        $this->actingAs($owner)
            ->get(route('weddings.preview', $wedding))
            ->assertOk()
            ->assertSee('Pratinjau draf')
            ->assertSee('Draf Rahasia Ayu')
            ->assertSee('Awal Kami')
            ->assertSee('Bertemu di kampus.');

        // A draft that was never published must stay unavailable to guests.
        $this->get(route('invitations.public', ['slug' => $wedding->slug]))->assertNotFound();
    }

    public function test_a_wedding_without_a_draft_redirects_back_to_the_editor(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_user_id' => $owner->getKey()]);

        $this->actingAs($owner)
            ->get(route('weddings.preview', $wedding))
            ->assertRedirect(route('weddings.edit', $wedding))
            ->assertSessionHasErrors('preview');
    }

    private function createWedding(User $owner): Wedding
    {
        return app(CreateWedding::class)->handle($owner, 'Pernikahan Pratinjau', $this->templateVersion);
    }
}
