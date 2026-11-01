<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Weddings\CreateWedding;
use App\Actions\Weddings\UpdateWedding;
use App\Http\Requests\StoreWeddingRequest;
use App\Http\Requests\UpdateWeddingRequest;
use App\Models\InvitationTemplate;
use App\Models\TemplateVersion;
use App\Models\Wedding;
use App\Support\Billing\TemplateAccess;
use App\Support\Guests\RsvpSummary;
use App\Support\Invitations\InvitationCopy;
use App\Support\Media\MediaQuota;
use App\Support\Templates\TemplatePresentation;
use App\Support\Templates\TemplateRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
