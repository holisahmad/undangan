<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Weddings\CreateWedding;
use App\Models\InvitationPublication;
use App\Models\ServiceEntitlement;
use App\Models\TemplateVersion;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingEvent;
use App\Models\WeddingPartner;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PublishInvitationTest extends TestCase
{
    use RefreshDatabase;

    private TemplateVersion $templateVersion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TemplateSeeder::class);
        $this->templateVersion = TemplateVersion::query()->firstOrFail();
    }

    public function test_publishing_without_an_active_entitlement_is_refused(): void
    {
        $owner = User::factory()->create();
        $wedding = $this->completeWedding($owner);

        $this->actingAs($owner)
            ->post(route('weddings.publish', $wedding))
            ->assertSessionHasErrors('publish');

        $this->assertDatabaseCount('invitation_publications', 0);
        $this->assertSame('draft', $wedding->fresh()->lifecycle_status->value);
    }

    public function test_publishing_without_couple_or_event_data_is_refused(): void
    {
        $owner = User::factory()->create();
        $wedding = $this->createWedding($owner);
        ServiceEntitlement::factory()->create(['wedding_id' => $wedding->getKey()]);

        $this->actingAs($owner)
            ->post(route('weddings.publish', $wedding))
            ->assertSessionHasErrors('publish');

        $this->assertDatabaseCount('invitation_publications', 0);
    }

    public function test_a_complete_wedding_publishes_an_immutable_snapshot(): void
    {
        $owner = User::factory()->create();
        $wedding = $this->completeWedding($owner);
        ServiceEntitlement::factory()->create([
            'wedding_id' => $wedding->getKey(),
            'expires_at' => now()->addMonths(6),
        ]);

        $this->actingAs($owner)
            ->post(route('weddings.publish', $wedding))
            ->assertRedirect(route('weddings.show', $wedding));

        $publication = InvitationPublication::query()->firstOrFail();

        $this->assertSame(1, $publication->version_number);
        $this->assertNotNull($publication->expires_at);
        $this->assertSame('Ayu', $publication->published_snapshot['couple'][0]['nickname']);
        $this->assertNotEmpty($publication->published_snapshot['events']);
        $this->assertSame('published', $wedding->fresh()->lifecycle_status->value);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'invitation.published',
            'resource_id' => $wedding->getKey(),
            'actor_user_id' => $owner->getKey(),
        ]);

        $wedding->draft()->update([
            'content_json' => array_replace_recursive($wedding->draft->content_json, [
                'cover' => ['title' => 'Judul Setelah Terbit'],
            ]),
        ]);

        $this->assertSame(
            $publication->published_snapshot,
            $publication->fresh()->published_snapshot,
        );
        $this->assertSame(
            'The Wedding Of Ayu & Bima',
            $publication->fresh()->published_snapshot['content']['cover']['title'],
        );
    }

    public function test_republishing_creates_a_new_active_version(): void
    {
        $owner = User::factory()->create();
        $wedding = $this->completeWedding($owner);
        ServiceEntitlement::factory()->create(['wedding_id' => $wedding->getKey()]);

        $this->actingAs($owner)->post(route('weddings.publish', $wedding));

        $wedding->draft()->update([
            'content_json' => array_replace_recursive($wedding->draft->content_json, [
                'cover' => ['title' => 'Judul Versi Dua'],
            ]),
        ]);

        $this->actingAs($owner)->post(route('weddings.publish', $wedding));

        $this->assertDatabaseCount('invitation_publications', 2);
        $this->assertSame(2, $wedding->activePublication()->firstOrFail()->version_number);
        $this->assertSame(2, $wedding->latestPublication()->firstOrFail()->version_number);
        $this->assertSame('Judul Versi Dua', $wedding->activePublication()->firstOrFail()->published_snapshot['content']['cover']['title']);
    }

    public function test_only_the_owner_can_publish_or_unpublish(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $wedding = $this->completeWedding($owner);
        ServiceEntitlement::factory()->create(['wedding_id' => $wedding->getKey()]);

        $this->actingAs($intruder)->post(route('weddings.publish', $wedding))->assertForbidden();
        $this->actingAs($intruder)->post(route('weddings.unpublish', $wedding))->assertForbidden();

        $this->assertDatabaseCount('invitation_publications', 0);
    }

    public function test_unpublishing_disables_the_public_link(): void
    {
        $owner = User::factory()->create();
        $wedding = $this->completeWedding($owner);
        ServiceEntitlement::factory()->create(['wedding_id' => $wedding->getKey()]);

        $this->actingAs($owner)->post(route('weddings.publish', $wedding));
        $this->actingAs($owner)
            ->post(route('weddings.unpublish', $wedding))
            ->assertRedirect(route('weddings.show', $wedding));

        $this->assertNull($wedding->activePublication()->first());
        $this->assertSame('unpublished', $wedding->fresh()->lifecycle_status->value);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'invitation.unpublished',
            'resource_id' => $wedding->getKey(),
        ]);
    }

    private function createWedding(User $owner): Wedding
    {
        return app(CreateWedding::class)->handle($owner, 'Pernikahan Uji', $this->templateVersion);
    }

    private function completeWedding(User $owner): Wedding
    {
        $wedding = $this->createWedding($owner);

        WeddingPartner::factory()->for($wedding)->create(['nickname' => 'Ayu']);
        WeddingPartner::factory()->for($wedding)->groom()->create(['nickname' => 'Bima']);
        WeddingEvent::factory()->for($wedding)->create();

        $wedding->draft()->update([
            'content_json' => array_replace_recursive($wedding->draft->content_json, [
                'cover' => ['title' => 'The Wedding Of Ayu & Bima'],
            ]),
        ]);

        return $wedding;
    }
}
