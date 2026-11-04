<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Weddings\CreateWedding;
use App\Actions\Weddings\UpdateWedding;
use App\Enums\AlbumVisibility;
use App\Http\Requests\StoreWeddingRequest;
use App\Http\Requests\UpdateWeddingRequest;
use App\Models\InvitationTemplate;
use App\Models\MediaAsset;
use App\Models\TemplateVersion;
use App\Models\Wedding;
use App\Support\Billing\TemplateAccess;
use App\Support\Guests\RsvpSummary;
use App\Support\Invitations\InvitationCopy;
use App\Support\Media\MediaQuota;
use App\Support\Media\UploadLimits;
use App\Support\Templates\TemplatePresentation;
use App\Support\Templates\TemplateRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

final class WeddingController extends Controller
{
    public function index(Request $request): View
    {
        $weddings = $request->user()
            ->weddings()
            ->with(['partners', 'events', 'activePublication'])
            ->latest()
            ->paginate(12);

        return view('weddings.index', [
            'weddings' => $weddings,
        ]);
    }

    public function create(TemplateRegistry $registry): View
    {
        // Nothing has been paid for yet, so every shipped theme is offered: the
        // package is picked when the order is placed and the card states which
        // package each design belongs to (docs/BUSINESS_MODEL.md).
        return view('weddings.create', [
            'templates' => $this->publishedTemplates($registry),
            'locked' => [],
        ]);
    }

    public function store(StoreWeddingRequest $request, CreateWedding $createWedding): RedirectResponse
    {
        $wedding = $createWedding->handle(
            $request->user(),
            $request->string('internal_title')->toString(),
            $request->templateVersion(),
        );

        return redirect()
            ->route('weddings.edit', $wedding)
            ->with('status', 'Draft undangan berhasil dibuat.');
    }

    public function show(Wedding $wedding, MediaQuota $quota, RsvpSummary $rsvpSummary): View
    {
        $this->authorize('view', $wedding);

        $wedding->load(['partners', 'events', 'activePublication', 'latestPublication', 'testimonial']);

        $publicUrl = route('invitations.public', ['slug' => $wedding->slug]);

        return view('weddings.show', [
            'wedding' => $wedding,
            'entitlement' => $wedding->activeInvitationEntitlement(),
            'rsvp' => $rsvpSummary->for($wedding),
            'photoCount' => $quota->photosUsed($wedding),
            'photoLimit' => $quota->photoLimit($wedding),
            'publicUrl' => $publicUrl,
            'shareMessage' => InvitationCopy::shareMessage($wedding, $publicUrl),
        ]);
    }

    public function edit(Wedding $wedding, TemplateRegistry $registry, TemplateAccess $access): View
    {
        $this->authorize('update', $wedding);

        $wedding->load([
            'partners',
            'events',
            'draft.templateVersion.template',
            'activePublication',
            'latestPublication',
        ]);

        $templates = $this->publishedTemplates($registry);
        $ceiling = $access->ceilingFor($wedding);

        // Versions the paid package does not unlock stay in the list, labelled
        // with what they need: hiding a design the owner can upgrade to is worse
        // than showing it as an option they have not bought yet.
        $locked = $templates
            ->filter(static fn (TemplateVersion $version): bool => ! $access->allows($ceiling, $version->template))
            ->mapWithKeys(static fn (TemplateVersion $version): array => [
                (string) $version->getKey() => TemplatePresentation::tierLabel($version->template->tier),
            ])
            ->all();

        return view('weddings.edit', [
            'wedding' => $wedding,
            'draft' => $wedding->draft,
            'entitlement' => $wedding->activeInvitationEntitlement(),
            'templates' => $templates,
            'locked' => $locked,
            'photoDisplay' => $wedding->photo_display ?? Wedding::PHOTO_DISPLAY_PREWEDDING,
            'coverId' => $wedding->cover_media_asset_id,
            ...$this->invitationPhotos($wedding),
        ]);
    }

    public function update(
        UpdateWeddingRequest $request,
        Wedding $wedding,
        UpdateWedding $updateWedding,
    ): RedirectResponse {
        $updateWedding->handle($wedding, $request->string('internal_title')->toString());

        return back()->with('status', 'Data undangan diperbarui.');
    }

    /**
     * Photos the couple may show inside the invitation: ready assets in their
     * public albums, newest first, each with a signed thumbnail for the picker.
     *
     * @return array{photos: Collection<int, MediaAsset>, photoThumbUrls: array<string, string>, limits: UploadLimits}
     */
    private function invitationPhotos(Wedding $wedding): array
    {
        $photos = $wedding->mediaAssets()
            ->ready()
            ->whereHas('album', static fn ($album) => $album->where('visibility', AlbumVisibility::Public))
            ->latest()
            ->limit(12)
            ->get();

        $ttl = (int) config('platform.media.signed_url_ttl_minutes', 15);

        return [
            'photos' => $photos,
            'photoThumbUrls' => $photos->mapWithKeys(static fn (MediaAsset $asset): array => [
                $asset->getKey() => URL::temporarySignedRoute(
                    'media.photos.download',
                    now()->addMinutes($ttl),
                    ['mediaAsset' => $asset->getKey(), 'variant' => 'thumb'],
                ),
            ])->all(),
            'limits' => UploadLimits::current(),
        ];
    }

    /**
     * @return Collection<int, TemplateVersion>
     */
    private function publishedTemplates(TemplateRegistry $registry): Collection
    {
        return TemplateVersion::query()
            ->where('status', 'published')
            ->whereIn('renderer_key', $registry->rendererKeys())
            ->with('template')
            ->get()
            ->filter(static fn (TemplateVersion $version): bool => $version->template !== null
                && $version->template->status === InvitationTemplate::STATUS_ACTIVE)
            // The curated catalogue order, not the renderer spelling, so the
            // picker and /tema never present the designs in a different sequence.
            ->sortBy([[static fn (TemplateVersion $version): int => (int) ($version->template->sort_order ?? 0), 'asc'], [static fn (TemplateVersion $version): string => strtolower((string) $version->template->name), 'asc']])
            ->values();
    }
}
