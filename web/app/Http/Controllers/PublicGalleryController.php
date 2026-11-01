<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\PublicInvitation;
use App\Enums\AlbumVisibility;
use App\Models\MediaAsset;
use App\Models\Wedding;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

/**
 * Public gallery for a published invitation. Only ready assets inside albums
 * marked public are exposed; photos without an album and private albums never
 * appear here (docs/MEDIA_STORAGE.md, section 4).
 */
final class PublicGalleryController extends Controller
{
    public function show(string $slug): View|Response
    {
        $wedding = Wedding::query()->where('slug', $slug)->first();

        if ($wedding === null) {
            abort(404);
        }

        $publication = $wedding->activePublication()->first();

        if ($publication === null) {
            $latest = $wedding->latestPublication()->first();

            if ($latest === null) {
                abort(404);
            }

            return response()->view('invitations.unavailable', [
                'reason' => $latest->hasExpired() ? 'expired' : 'unpublished',
            ], 410);
        }

        // Assets are eager-loaded with the albums: this route is public and
        // crawler-facing, so one query per album would scale with the album
        // count instead of staying at two (docs/TESTING.md, query budgets).
        $albums = $wedding->mediaAlbums()
            ->where('visibility', AlbumVisibility::Public)
            ->with(['assets' => static fn ($query) => $query->ready()->orderBy('created_at')])
            ->orderBy('title')
            ->get()
            ->map(static fn ($album): array => [
                'album' => $album,
                'assets' => $album->assets,
            ])
            ->filter(static fn (array $group): bool => $group['assets']->isNotEmpty())
            ->values();

        $ttl = (int) config('platform.media.signed_url_ttl_minutes', 15);
        $thumbUrls = [];
        $previewUrls = [];

        foreach ($albums as $group) {
            /** @var MediaAsset $asset */
            foreach ($group['assets'] as $asset) {
                $thumbUrls[$asset->getKey()] = URL::temporarySignedRoute(
                    'media.photos.download',
                    now()->addMinutes($ttl),
                    ['mediaAsset' => $asset->getKey(), 'variant' => 'thumb'],
                );
                $previewUrls[$asset->getKey()] = URL::temporarySignedRoute(
                    'media.photos.download',
                    now()->addMinutes($ttl),
                    ['mediaAsset' => $asset->getKey(), 'variant' => 'preview'],
                );
            }
        }

        return view('gallery.public', [
            'invitation' => PublicInvitation::fromSnapshot($wedding->slug, $publication->published_snapshot),
            'albums' => $albums,
            'thumbUrls' => $thumbUrls,
            'previewUrls' => $previewUrls,
        ]);
    }
}
