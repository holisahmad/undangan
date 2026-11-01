<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\InvitationPublication;
use App\Models\MediaAlbum;
use App\Models\MediaAsset;
use App\Models\User;
use App\Models\Wedding;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 6 performance guard: the read-heavy screens must not grow with the
 * number of rows. Budgets are deliberately loose enough for CI variance but
 * tight enough to catch an accidental N+1 (docs/DEPLOYMENT.md, section 6).
 */
final class QueryBudgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('media');
    }

    public function test_the_public_invitation_uses_a_fixed_number_of_queries(): void
    {
        $wedding = Wedding::factory()->published()->create();
        InvitationPublication::factory()->for($wedding)->create();

        $queries = $this->countQueries(function () use ($wedding): void {
            $this->get(route('invitations.public', ['slug' => $wedding->slug]))->assertOk();
        });

        fwrite(STDERR, "\npublic invitation queries: {$queries}\n");

        $this->assertLessThanOrEqual(8, $queries);
    }

    public function test_the_gallery_page_does_not_query_per_photo(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->for($owner, 'owner')->create();

        foreach (range(1, 6) as $index) {
            MediaAsset::factory()->for($wedding)->create(['original_filename' => "foto-{$index}.jpg"]);
        }

        $queries = $this->countQueries(function () use ($owner, $wedding): void {
            $this->actingAs($owner)
                ->get(route('weddings.gallery.index', $wedding))
                ->assertOk();
        });

        fwrite(STDERR, "\ngallery queries (6 photos): {$queries}\n");

        $this->assertLessThanOrEqual(16, $queries);
    }

    public function test_the_dashboard_does_not_query_per_wedding(): void
    {
        $owner = User::factory()->create();

        Wedding::factory()->count(4)->for($owner, 'owner')->create();

        $queries = $this->countQueries(function () use ($owner): void {
            $this->actingAs($owner)->get(route('dashboard'))->assertOk();
        });

        fwrite(STDERR, "\ndashboard queries (4 weddings): {$queries}\n");

        $this->assertLessThanOrEqual(10, $queries);
    }

    /**
     * The public gallery is crawler-facing, so it must not answer one query per
     * album (docs/MEDIA_STORAGE.md, section 4).
     */
    public function test_the_public_gallery_does_not_query_per_album(): void
    {
        $wedding = Wedding::factory()->published()->create();
        InvitationPublication::factory()->for($wedding)->create();

        foreach (range(1, 5) as $index) {
            $album = MediaAlbum::factory()->for($wedding)->public()->create(['title' => "Album {$index}"]);

            MediaAsset::factory()->count(2)->for($wedding)->create(['album_id' => $album->getKey()]);
        }

        $queries = $this->countQueries(function () use ($wedding): void {
            $this->get(route('invitations.gallery', ['slug' => $wedding->slug]))->assertOk();
        });

        fwrite(STDERR, "\npublic gallery queries (5 albums): {$queries}\n");

        // One query per album would put five more on top of the fixed ones.
        $this->assertLessThanOrEqual(8, $queries);
    }

    private function countQueries(Closure $callback): int
    {
        $count = 0;

        DB::listen(static function () use (&$count): void {
            $count++;
        });

        $callback();

        return $count;
    }
}
