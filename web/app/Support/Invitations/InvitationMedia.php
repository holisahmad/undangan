<?php

declare(strict_types=1);

namespace App\Support\Invitations;

use App\Data\PublicInvitation;
use App\Enums\AlbumVisibility;
use App\Models\MediaAsset;
use App\Models\Wedding;
use Illuminate\Support\Facades\URL;

/**
 * Photos shown inside an invitation.
 *
 * A real invitation may only show photos the couple themselves published: the
 * ready assets of their public albums, served through the same short-lived
 * signed URLs the public gallery uses. Stock demo photography is reserved for
 * the fictional template demos, because a guest receiving "Ayu & Bima" photos
 * on someone else's invitation is both confusing and dishonest.
 *
 * Every accessor returns null/[] when no photo is available, so a renderer can
 * fall back to a monogram instead of printing a broken image.
 */
final class InvitationMedia
{
    private const DEMO_HERO = 'images/demo/couple-hero.webp';

    private const DEMO_BRIDE = 'images/demo/bride.webp';

    private const DEMO_GROOM = 'images/demo/groom.webp';

    /**
     * @var list<string>
     */
    private const DEMO_GALLERY = [
        'images/demo/gallery-1.webp',
        'images/demo/gallery-2.webp',
        'images/demo/gallery-3.webp',
        'images/demo/gallery-4.webp',
    ];

    private const MAX_PHOTOS = 6;

    /**
     * @param  list<string>  $gallery
     */
    private function __construct(
        private readonly ?string $hero,
        private readonly ?string $bride,
        private readonly ?string $groom,
        private readonly array $gallery,
    ) {}

    /**
     * A controller that already loaded the wedding passes it in, which keeps the
     * public invitation at a fixed query budget instead of looking the row up a
     * second time.
     */
    public static function for(PublicInvitation $invitation, bool $demo = false, ?Wedding $wedding = null): self
    {
        if ($demo) {
            return new self(
                hero: asset(self::DEMO_HERO),
                bride: asset(self::DEMO_BRIDE),
                groom: asset(self::DEMO_GROOM),
                gallery: array_map(static fn (string $path): string => asset($path), self::DEMO_GALLERY),
            );
        }

        $wedding ??= Wedding::query()->where('slug', $invitation->slug)->first();

        // Two independent ways to end up without imagery: the couple chose a
        // monogram, or they simply have no published pre-wedding photos yet.
        if ($wedding === null || ! $wedding->showsPreWeddingPhotos()) {
            return new self(hero: null, bride: null, groom: null, gallery: []);
        }

        $photos = self::publishedPhotos($wedding);

        return new self(
            hero: $photos[0] ?? null,
            bride: $photos[1] ?? null,
            groom: $photos[2] ?? null,
            gallery: $photos,
        );
    }

    public function hero(): ?string
    {
        return $this->hero;
    }

    public function portrait(string $type): ?string
    {
        return $type === 'bride' ? $this->bride : $this->groom;
    }

    /**
     * @return list<string>
     */
    public function gallery(): array
    {
        return $this->gallery;
    }

    public function hasPhotos(): bool
    {
        return $this->hero !== null;
    }

    /**
     * Ready assets inside the wedding's public albums, signed for the configured
     * media TTL. The photo the couple chose as cover comes first so it opens the
     * invitation; the rest follow in upload order. Private albums and anything
     * still processing are never returned.
     *
     * @return list<string>
     */
    private static function publishedPhotos(Wedding $wedding): array
    {
        $coverId = (string) ($wedding->cover_media_asset_id ?? '');

        $assets = MediaAsset::query()
            ->ready()
            ->whereHas('album', static fn ($album) => $album
                ->where('wedding_id', $wedding->getKey())
                ->where('visibility', AlbumVisibility::Public))
            ->when($coverId !== '', static fn ($query) => $query
                ->orderByRaw('case when id = ? then 0 else 1 end', [$coverId]))
            ->orderBy('created_at')
            ->limit(self::MAX_PHOTOS)
            ->get();

        $ttl = (int) config('platform.media.signed_url_ttl_minutes', 15);

        return $assets
            ->map(static fn (MediaAsset $asset): string => URL::temporarySignedRoute(
                'media.photos.download',
                now()->addMinutes($ttl),
                ['mediaAsset' => $asset->getKey(), 'variant' => 'preview'],
            ))
            ->values()
            ->all();
    }
}
