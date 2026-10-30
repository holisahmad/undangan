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
        return view('weddings.create', [
            'templates' => $this->publishedTemplates($registry),
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

    public function show(Wedding $wedding): View
    {
        $this->authorize('view', $wedding);

        $wedding->load(['partners', 'events', 'activePublication', 'latestPublication']);

        return view('weddings.show', [
            'wedding' => $wedding,
            'entitlement' => $wedding->activeInvitationEntitlement(),
        ]);
    }

    public function edit(Wedding $wedding, TemplateRegistry $registry): View
    {
        $this->authorize('update', $wedding);

        $wedding->load([
            'partners',
            'events',
            'draft.templateVersion.template',
            'activePublication',
            'latestPublication',
        ]);

        return view('weddings.edit', [
            'wedding' => $wedding,
            'draft' => $wedding->draft,
            'entitlement' => $wedding->activeInvitationEntitlement(),
            'templates' => $this->publishedTemplates($registry),
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
            ->orderBy('renderer_key')
            ->get()
            ->filter(static fn (TemplateVersion $version): bool => $version->template !== null
                && $version->template->status === InvitationTemplate::STATUS_ACTIVE);
    }
}
