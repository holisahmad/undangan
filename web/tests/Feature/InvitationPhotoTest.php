<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Data\PublicInvitation;
use App\Enums\AlbumVisibility;
use App\Enums\MediaStatus;
use App\Models\InvitationPublication;
use App\Models\MediaAlbum;
use App\Models\MediaAsset;
use App\Models\User;
use App\Models\Wedding;
use App\Support\Invitations\InvitationMedia;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Pre-wedding photos belong to the couple: they upload their own pictures from
 * the invitation editor, pick the cover, or ask for a monogram instead. A real
 * invitation never shows the stock photography of a template demo.
 */
final class InvitationPhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('media');
        config(['platform.media.respect_php_limits' => false]);
        $this->seed(TemplateSeeder::class);
    }

    public function test_an_owner_uploads_a_photo_into_the_public_prewedding_album(): void
    {
        [$owner, $wedding] = $this->ownerWedding();

        $this->actingAs($owner)
            ->post(route('weddings.photos.store', $wedding), [
                'photos' => [UploadedFile::fake()->image('prewedding pantai.png', 1600, 1200)],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $album = MediaAlbum::query()->sole();
        $asset = MediaAsset::query()->sole();

        $this->assertSame('Prewedding', $album->title);
        $this->assertSame(AlbumVisibility::Public, $album->visibility);
        $this->assertSame($album->getKey(), $asset->album_id);
        $this->assertSame(MediaStatus::Ready, $asset->status);

        // The first upload is the photo the couple expects on the cover.
        $this->assertSame($asset->getKey(), $wedding->refresh()->cover_media_asset_id);

        $this->actingAs($owner)
            ->get(route('weddings.edit', $wedding))
            ->assertOk()
            ->assertSee('Foto pre-wedding')
            ->assertSee('Prewedding');
    }

    public function test_the_uploaded_photo_replaces_the_template_image_on_the_invitation(): void
    {
        [$owner, $wedding] = $this->ownerWedding();
        InvitationPublication::factory()->for($wedding)->create();

        $this->actingAs($owner)
            ->post(route('weddings.photos.store', $wedding), [
                'photos' => [UploadedFile::fake()->image('kita.png')],
            ])
            ->assertSessionHasNoErrors();

        $asset = MediaAsset::query()->sole();

        $this->get(route('invitations.public', ['slug' => $wedding->slug]))
            ->assertOk()
            ->assertSee('/media/photos/'.$asset->getKey().'/download', false)
            ->assertDontSee('images/demo/', false);
    }

    public function test_a_couple_without_photos_can_switch_the_invitation_to_a_monogram(): void
    {
        [$owner, $wedding] = $this->ownerWedding();
        InvitationPublication::factory()->for($wedding)->create();

        $this->actingAs($owner)
            ->post(route('weddings.photos.store', $wedding), [
                'photos' => [UploadedFile::fake()->image('kita.png')],
            ]);

        $this->actingAs($owner)
            ->patch(route('weddings.photos.display', $wedding), [
                'photo_display' => Wedding::PHOTO_DISPLAY_MONOGRAM,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(Wedding::PHOTO_DISPLAY_MONOGRAM, $wedding->refresh()->photo_display);

        $this->get(route('invitations.public', ['slug' => $wedding->slug]))
            ->assertOk()
            ->assertSee('inv-monogram', false)
            ->assertDontSee('/media/photos/', false);

        // The photos stay stored; turning them back on is one click away.
        $this->assertSame(1, MediaAsset::query()->ready()->count());
    }

    public function test_the_chosen_cover_opens_the_invitation(): void
    {
        [$owner, $wedding] = $this->ownerWedding();
        $publication = InvitationPublication::factory()->for($wedding)->create();
        $album = MediaAlbum::factory()->for($wedding)->create(['visibility' => AlbumVisibility::Public]);

        $photos = MediaAsset::factory()->count(3)->for($wedding)->create([
            'album_id' => $album->getKey(),
        ]);

        $cover = $photos->last();

        $this->actingAs($owner)
            ->patch(route('weddings.photos.cover', [$wedding, $cover]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame($cover->getKey(), $wedding->refresh()->cover_media_asset_id);

        $invitation = PublicInvitation::fromSnapshot($wedding->slug, $publication->published_snapshot);
        $hero = InvitationMedia::for($invitation, false, $wedding)->hero();

        $this->assertNotNull($hero);
        $this->assertStringContainsString('/media/photos/'.$cover->getKey().'/download', $hero);
    }

    public function test_another_account_cannot_upload_or_pick_a_cover(): void
    {
        [, $wedding] = $this->ownerWedding();
        $intruder = User::factory()->create();
        $asset = MediaAsset::factory()->for($wedding)->create();

        $this->actingAs($intruder)
            ->post(route('weddings.photos.store', $wedding), [
                'photos' => [UploadedFile::fake()->image('asing.png')],
            ])
            ->assertForbidden();

        $this->actingAs($intruder)
            ->patch(route('weddings.photos.cover', [$wedding, $asset]))
            ->assertForbidden();

        $this->actingAs($intruder)
            ->patch(route('weddings.photos.display', $wedding), [
                'photo_display' => Wedding::PHOTO_DISPLAY_MONOGRAM,
            ])
            ->assertForbidden();

        // Only the fixture photo exists: the intruder's upload never landed and
        // the cover is still unset.
        $this->assertSame(1, MediaAsset::query()->count());
        $this->assertNull($wedding->refresh()->cover_media_asset_id);
    }

    public function test_deleting_the_cover_photo_clears_the_pointer(): void
    {
        [$owner, $wedding] = $this->ownerWedding();

        $this->actingAs($owner)
            ->post(route('weddings.photos.store', $wedding), [
                'photos' => [UploadedFile::fake()->image('kita.png')],
            ]);

        $asset = MediaAsset::query()->sole();

        $this->actingAs($owner)
            ->delete(route('weddings.gallery.photos.destroy', [$wedding, $asset]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertNull($wedding->refresh()->cover_media_asset_id);
        $this->assertSame(MediaStatus::Deleted, $asset->refresh()->status);
    }

    public function test_a_photo_from_another_wedding_cannot_be_deleted(): void
    {
        [$owner, $wedding] = $this->ownerWedding();
        $foreign = MediaAsset::factory()->create();

        $this->actingAs($owner)
            ->delete(route('weddings.gallery.photos.destroy', [$wedding, $foreign]))
            ->assertNotFound();

        $this->assertSame(MediaStatus::Ready, $foreign->refresh()->status);
    }

    /**
     * @return array{0: User, 1: Wedding}
     */
    private function ownerWedding(): array
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->published()->for($owner, 'owner')->create();

        return [$owner, $wedding];
    }
}
