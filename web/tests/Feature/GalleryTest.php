<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ArchiveStatus;
use App\Enums\MediaStatus;
use App\Models\ArchiveJob;
use App\Models\AuditLog;
use App\Models\InvitationPublication;
use App\Models\MediaAlbum;
use App\Models\MediaAsset;
use App\Models\User;
use App\Models\Wedding;
use App\Support\Media\UploadLimits;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;
use ZipArchive;

/**
 * Gallery and archive: quota, private object storage, signed downloads and
 * retention. Media bytes are never served from a public path.
 */
final class GalleryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('media');

        // Tests assert the product ceilings, not whatever php.ini the CI host
        // happens to ship (UploadLimitsTest covers the clamping itself).
        config(['platform.media.respect_php_limits' => false]);
    }

    public function test_an_owner_uploads_a_photo_with_preview_and_thumbnail(): void
    {
        [$owner, $wedding] = $this->ownerWedding();

        $this->actingAs($owner)
            ->post(route('weddings.gallery.photos.store', $wedding), [
                'photos' => [UploadedFile::fake()->image('liburan keluarga.png', 1600, 1200)],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $asset = MediaAsset::query()->sole();
        $disk = Storage::disk('media');

        $this->assertSame(MediaStatus::Ready, $asset->status);
        $this->assertSame('liburan keluarga.png', $asset->original_filename);
        $this->assertSame(1600, $asset->width);
        $this->assertNull($asset->album_id);
        $this->assertTrue($disk->exists($asset->object_key));
        $this->assertTrue($disk->exists($asset->preview_object_key));
        $this->assertTrue($disk->exists($asset->thumbnail_object_key));

        $preview = getimagesizefromstring((string) $disk->get((string) $asset->preview_object_key));
        $thumbnail = getimagesizefromstring((string) $disk->get((string) $asset->thumbnail_object_key));

        $this->assertSame(1280, $preview[0]);
        $this->assertSame(400, $thumbnail[0]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'media.uploaded',
            'resource_id' => $asset->getKey(),
        ]);
    }

    public function test_another_account_cannot_read_or_write_the_gallery(): void
    {
        [$owner, $wedding] = $this->ownerWedding();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->get(route('weddings.gallery.index', $wedding))
            ->assertForbidden();

        $this->actingAs($intruder)
            ->post(route('weddings.gallery.photos.store', $wedding), [
                'photos' => [UploadedFile::fake()->image('foto.png')],
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('media_assets', 0);
    }

    public function test_an_asset_from_another_wedding_is_not_reachable_through_the_route(): void
    {
        [$owner, $wedding] = $this->ownerWedding();
        $otherWedding = Wedding::factory()->create();
        $asset = MediaAsset::factory()->for($otherWedding)->create();

        $this->actingAs($owner)
            ->delete(route('weddings.gallery.photos.destroy', [$wedding, $asset]))
            ->assertNotFound();

        $this->assertSame(MediaStatus::Ready, $asset->refresh()->status);
    }

    public function test_an_upload_beyond_the_package_quota_is_rejected(): void
    {
        [$owner, $wedding] = $this->ownerWedding(photoLimit: 1);

        $this->actingAs($owner)
            ->post(route('weddings.gallery.photos.store', $wedding), [
                'photos' => [UploadedFile::fake()->image('satu.png')],
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($owner)
            ->post(route('weddings.gallery.photos.store', $wedding), [
                'photos' => [UploadedFile::fake()->image('dua.png')],
            ])
            ->assertSessionHasErrors('photos');

        $this->assertDatabaseCount('media_assets', 1);
    }

    public function test_a_file_that_is_not_an_image_is_rejected(): void
    {
        [$owner, $wedding] = $this->ownerWedding();

        $this->actingAs($owner)
            ->post(route('weddings.gallery.photos.store', $wedding), [
                'photos' => [UploadedFile::fake()->create('dokumen.pdf', 20, 'application/pdf')],
            ])
            ->assertSessionHasErrors('photos.0');

        $this->assertDatabaseCount('media_assets', 0);
    }

    public function test_a_photo_larger_than_the_per_file_limit_is_rejected_with_the_effective_size(): void
    {
        config(['platform.media.max_file_kb' => 300]);
        [$owner, $wedding] = $this->ownerWedding();

        $this->actingAs($owner)
            ->post(route('weddings.gallery.photos.store', $wedding), [
                'photos' => [UploadedFile::fake()->image('besar.png')->size(400)],
            ])
            ->assertSessionHasErrors(['photos.0' => 'Ukuran tiap foto maksimal 1 MB.']);

        $this->assertDatabaseCount('media_assets', 0);
    }

    public function test_the_combined_size_of_one_submit_is_capped(): void
    {
        config(['platform.media.max_upload_kb' => 4096]);
        [$owner, $wedding] = $this->ownerWedding();

        $this->actingAs($owner)
            ->post(route('weddings.gallery.photos.store', $wedding), [
                'photos' => [
                    UploadedFile::fake()->image('satu.png')->size(1800),
                    UploadedFile::fake()->image('dua.png')->size(1800),
                    UploadedFile::fake()->image('tiga.png')->size(1800),
                ],
            ])
            ->assertSessionHasErrors('photos');

        $this->assertDatabaseCount('media_assets', 0);
    }

    public function test_the_gallery_page_states_the_ceilings_that_apply(): void
    {
        config(['platform.media.max_upload_kb' => 24576]);
        [$owner, $wedding] = $this->ownerWedding();

        $this->actingAs($owner)
            ->get(route('weddings.gallery.index', $wedding))
            ->assertOk()
            ->assertSee('8 MB per foto', false)
            ->assertSee('24,0 MB sekali kirim', false)
            ->assertSee('data-max-total-bytes="'.(24576 * 1024).'"', false);
    }

    public function test_an_oversized_request_answers_with_the_413_page_and_status(): void
    {
        $postMaxKb = UploadLimits::toKb((string) ini_get('post_max_size'));

        if ($postMaxKb === null) {
            $this->markTestSkipped('This host does not cap post_max_size.');
        }

        [$owner, $wedding] = $this->ownerWedding();

        // Symfony reads CONTENT_LENGTH from the server bag, so the global
        // middleware aborts exactly like it does on a real PHP host. The body
        // only has to clear whatever cap this machine actually runs.
        $oversized = ($postMaxKb + 1024) * 1024;

        $this->actingAs($owner)->call(
            'POST',
            route('weddings.gallery.photos.store', $wedding),
            [],
            [],
            [],
            ['CONTENT_LENGTH' => $oversized],
        )->assertStatus(413)->assertSee('Unggahan terlalu besar', false);

        $this->assertDatabaseCount('media_assets', 0);
    }

    public function test_the_too_large_page_explains_the_limits_without_a_session(): void
    {
        config([
            'platform.media.max_file_kb' => 8192,
            'platform.media.max_upload_kb' => 24576,
            'platform.media.max_files_per_upload' => 10,
        ]);

        $html = view('errors.413')->render();

        $this->assertStringContainsString('Unggahan terlalu besar', $html);
        $this->assertStringContainsString('8 MB', $html);
        $this->assertStringContainsString('24,0 MB', $html);
        $this->assertStringContainsString('10 foto', $html);
    }

    public function test_an_album_from_another_wedding_cannot_be_used_for_an_upload(): void
    {
        [$owner, $wedding] = $this->ownerWedding();
        $foreignAlbum = MediaAlbum::factory()->create();

        $this->actingAs($owner)
            ->post(route('weddings.gallery.photos.store', $wedding), [
                'album_id' => $foreignAlbum->getKey(),
                'photos' => [UploadedFile::fake()->image('foto.png')],
            ])
            ->assertSessionHasErrors('album_id');

        $this->assertDatabaseCount('media_assets', 0);
    }

    public function test_the_public_gallery_only_shows_ready_photos_from_public_albums(): void
    {
        $wedding = Wedding::factory()->published()->create();
        InvitationPublication::factory()->for($wedding)->create();

        $public = MediaAlbum::factory()->for($wedding)->public()->create(['title' => 'Resepsi']);
        $private = MediaAlbum::factory()->for($wedding)->create(['title' => 'Keluarga']);

        MediaAsset::factory()->for($wedding)->create([
            'album_id' => $public->id,
            'original_filename' => 'terlihat.jpg',
        ]);
        MediaAsset::factory()->for($wedding)->uploaded()->create([
            'album_id' => $public->id,
            'original_filename' => 'belum-siap.jpg',
        ]);
        MediaAsset::factory()->for($wedding)->create([
            'album_id' => $private->id,
            'original_filename' => 'rahasia.jpg',
        ]);

        $this->get(route('invitations.gallery', ['slug' => $wedding->slug]))
            ->assertOk()
            ->assertSee('Resepsi')
            ->assertSee('terlihat.jpg')
            ->assertDontSee('belum-siap.jpg')
            ->assertDontSee('rahasia.jpg');
    }

    public function test_the_public_gallery_requires_an_active_publication(): void
    {
        $wedding = Wedding::factory()->published()->create();
        InvitationPublication::factory()->for($wedding)->create(['unpublished_at' => now()]);

        $this->get(route('invitations.gallery', ['slug' => $wedding->slug]))
            ->assertStatus(410);
    }

    public function test_media_downloads_require_a_valid_signature(): void
    {
        [$owner, $wedding] = $this->ownerWedding();
        $asset = $this->assetWithFile($wedding);

        $this->get(route('media.photos.download', ['mediaAsset' => $asset->getKey(), 'variant' => 'thumb']))
            ->assertForbidden();

        $signed = URL::temporarySignedRoute('media.photos.download', now()->addMinutes(5), [
            'mediaAsset' => $asset->getKey(),
            'variant' => 'thumb',
        ]);

        $this->get($signed)->assertOk()->assertHeader('Content-Type', 'image/jpeg');
    }

    public function test_an_expired_signed_url_is_rejected(): void
    {
        [, $wedding] = $this->ownerWedding();
        $asset = $this->assetWithFile($wedding);

        $expired = URL::temporarySignedRoute('media.photos.download', now()->subMinute(), [
            'mediaAsset' => $asset->getKey(),
            'variant' => 'original',
        ]);

        $this->get($expired)->assertForbidden();
    }

    public function test_deleting_a_photo_removes_objects_and_records_an_audit(): void
    {
        [$owner, $wedding] = $this->ownerWedding();
        $asset = $this->assetWithFile($wedding);

        $this->actingAs($owner)
            ->delete(route('weddings.gallery.photos.destroy', [$wedding, $asset]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $asset->refresh();

        $this->assertSame(MediaStatus::Deleted, $asset->status);
        $this->assertNotNull($asset->deleted_at);
        $this->assertFalse(Storage::disk('media')->exists((string) $asset->object_key));

        $audit = AuditLog::query()->where('action', 'media.deleted')->sole();
        $this->assertSame($asset->getKey(), $audit->resource_id);
    }

    public function test_an_archive_contains_only_the_owners_ready_photos(): void
    {
        [$owner, $wedding] = $this->ownerWedding();
        $first = $this->assetWithFile($wedding, 'satu.jpg');
        $second = $this->assetWithFile($wedding, 'dua.jpg');

        $otherWedding = Wedding::factory()->create();
        $foreign = $this->assetWithFile($otherWedding, 'asing.jpg');

        $this->actingAs($owner)
            ->post(route('weddings.gallery.archives.store', $wedding))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $archive = ArchiveJob::query()->sole();
        $disk = Storage::disk('media');

        $this->assertSame(ArchiveStatus::Ready, $archive->status);
        $this->assertSame(2, $archive->asset_count);
        $this->assertNotNull($archive->expires_at);
        $this->assertTrue($disk->exists((string) $archive->object_key));

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($disk->path((string) $archive->object_key)) === true);

        $names = [];

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $names[] = (string) $zip->getNameIndex($index);
        }

        $zip->close();

        $this->assertCount(2, $names);
        $this->assertSame('001-satu.jpg', $names[0]);
        $this->assertSame('002-dua.jpg', $names[1]);
        $this->assertNotContains('asing.jpg', $names);

        $manifest = collect($archive->manifest_json)->pluck('asset_id')->all();
        $this->assertContains($first->getKey(), $manifest);
        $this->assertContains($second->getKey(), $manifest);
        $this->assertNotContains($foreign->getKey(), $manifest);
    }

    public function test_an_expired_archive_is_not_downloadable_and_is_cleaned_up(): void
    {
        [, $wedding] = $this->ownerWedding();
        $archive = ArchiveJob::factory()->for($wedding)->expired()->create([
            'object_key' => 'archives/'.$wedding->getKey().'/lama.zip',
        ]);

        Storage::disk('media')->put((string) $archive->object_key, 'zip');

        $signed = URL::temporarySignedRoute('media.archives.download', now()->addMinutes(30), [
            'archive' => $archive->getKey(),
        ]);

        $this->get($signed)->assertStatus(410);

        $this->artisan('media:cleanup')->assertExitCode(0);

        $archive->refresh();
        $this->assertSame(ArchiveStatus::Expired, $archive->status);
        $this->assertFalse(Storage::disk('media')->exists('archives/'.$wedding->getKey().'/lama.zip'));
    }

    public function test_media_cleanup_keeps_archives_before_their_expiry(): void
    {
        [, $wedding] = $this->ownerWedding();
        $archive = ArchiveJob::factory()->for($wedding)->create();
        Storage::disk('media')->put((string) $archive->object_key, 'zip');

        $this->artisan('media:cleanup')->assertExitCode(0);

        $this->assertSame(ArchiveStatus::Ready, $archive->refresh()->status);
        $this->assertTrue(Storage::disk('media')->exists((string) $archive->object_key));
    }

    public function test_reconcile_reports_missing_and_orphan_objects(): void
    {
        [, $wedding] = $this->ownerWedding();
        $asset = $this->assetWithFile($wedding);

        Storage::disk('media')->delete((string) $asset->object_key);
        Storage::disk('media')->put('weddings/yatim.jpg', 'x');

        $this->artisan('media:reconcile', ['--strict' => true])
            ->expectsOutputToContain('File hilang: 1')
            ->expectsOutputToContain('Objek yatim: 1')
            ->assertExitCode(1);
    }

    public function test_albums_are_unique_per_wedding_and_photos_survive_album_deletion(): void
    {
        [$owner, $wedding] = $this->ownerWedding();
        $album = MediaAlbum::factory()->for($wedding)->create(['title' => 'Resepsi']);
        $asset = MediaAsset::factory()->for($wedding)->create(['album_id' => $album->id]);

        $this->actingAs($owner)
            ->post(route('weddings.gallery.albums.store', $wedding), [
                'title' => 'Resepsi',
                'visibility' => 'public',
            ])
            ->assertSessionHasErrors('title');

        $this->actingAs($owner)
            ->delete(route('weddings.gallery.albums.destroy', [$wedding, $album]))
            ->assertRedirect();

        $this->assertDatabaseCount('media_albums', 0);
        $this->assertNull($asset->refresh()->album_id);
        $this->assertSame(MediaStatus::Ready, $asset->status);
    }

    public function test_a_failed_archive_can_be_rebuilt(): void
    {
        [$owner, $wedding] = $this->ownerWedding();
        $this->assetWithFile($wedding, 'foto.jpg');

        $archive = ArchiveJob::factory()->for($wedding)->failed()->create();

        $this->actingAs($owner)
            ->post(route('weddings.gallery.archives.retry', [$wedding, $archive]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $archive->refresh();

        $this->assertSame(ArchiveStatus::Ready, $archive->status);
        $this->assertSame(1, $archive->asset_count);
        $this->assertTrue(Storage::disk('media')->exists((string) $archive->object_key));
    }

    /**
     * @return array{0: User, 1: Wedding}
     */
    private function ownerWedding(?int $photoLimit = null): array
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->for($owner, 'owner')->create();

        if ($photoLimit !== null) {
            $wedding->entitlements()->create([
                'entitlement_type' => 'invitation',
                'starts_at' => now()->subDay(),
                'expires_at' => now()->addYear(),
                'limits_json' => ['features' => ['gallery_photos' => ['limit' => $photoLimit]]],
                'status' => 'active',
            ]);
        }

        return [$owner, $wedding];
    }

    private function assetWithFile(Wedding $wedding, string $filename = 'foto.jpg'): MediaAsset
    {
        $asset = MediaAsset::factory()->for($wedding)->create(['original_filename' => $filename]);

        Storage::disk('media')->put((string) $asset->object_key, 'binary');
        Storage::disk('media')->put((string) $asset->preview_object_key, 'binary');
        Storage::disk('media')->put((string) $asset->thumbnail_object_key, 'binary');

        return $asset;
    }
}
