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

final class InvitationDraftTest extends TestCase
{
    use RefreshDatabase;

    private TemplateVersion $templateVersion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TemplateSeeder::class);
        $this->templateVersion = TemplateVersion::query()->firstOrFail();
    }

    public function test_the_owner_can_save_draft_content(): void
    {
        $owner = User::factory()->create();
        $wedding = $this->createWedding($owner);

        $content = CreateWedding::defaultContent();
        $content['cover']['title'] = 'The Wedding Of Ayu & Bima';
        $content['story'] = ['heading' => 'Awal Kami', 'body' => 'Bertemu di kampus.'];

        $this->actingAs($owner)
            ->put(route('weddings.draft.update', $wedding), [
                'template_version_id' => $this->templateVersion->getKey(),
                'content' => $content,
            ])
            ->assertRedirect(route('weddings.edit', $wedding));

        $draft = $wedding->draft()->firstOrFail();

        $this->assertSame(2, $draft->revision);
        $this->assertSame('The Wedding Of Ayu & Bima', $draft->content_json['cover']['title']);
        $this->assertSame('Awal Kami', $draft->content_json['story']['heading']);
    }

    public function test_content_that_breaks_the_template_schema_is_rejected(): void
    {
        $owner = User::factory()->create();
        $wedding = $this->createWedding($owner);
        $original = $wedding->draft()->firstOrFail()->content_json;

        $this->actingAs($owner)
            ->put(route('weddings.draft.update', $wedding), [
                'template_version_id' => $this->templateVersion->getKey(),
                'content' => ['cover' => ['title' => ''], 'features' => []],
            ])
            ->assertSessionHasErrors(['cover.title', 'features.show_story', 'features.show_events', 'features.show_map']);

        $this->assertSame($original, $wedding->draft()->firstOrFail()->content_json);
    }

    public function test_another_customer_cannot_save_someone_elses_draft(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $wedding = $this->createWedding($owner);
        $original = $wedding->draft()->firstOrFail()->content_json;

        $this->actingAs($intruder)
            ->put(route('weddings.draft.update', $wedding), [
                'template_version_id' => $this->templateVersion->getKey(),
                'content' => CreateWedding::defaultContent(),
            ])
            ->assertForbidden();

        $this->assertSame($original, $wedding->draft()->firstOrFail()->content_json);
    }

    public function test_a_template_version_that_is_not_published_is_rejected(): void
    {
        $owner = User::factory()->create();
        $wedding = $this->createWedding($owner);
        $draftVersion = TemplateVersion::factory()->draft()->create();

        $this->actingAs($owner)
            ->put(route('weddings.draft.update', $wedding), [
                'template_version_id' => $draftVersion->getKey(),
                'content' => CreateWedding::defaultContent(),
            ])
            ->assertSessionHasErrors('template_version_id');
    }

    private function createWedding(User $owner): Wedding
    {
        return app(CreateWedding::class)->handle($owner, 'Pernikahan Uji', $this->templateVersion);
    }
}
