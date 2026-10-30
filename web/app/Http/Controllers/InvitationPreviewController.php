<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\PublicInvitation;
use App\Models\Wedding;
use App\Support\Invitations\InvitationSnapshot;
use App\Support\Templates\TemplateRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class InvitationPreviewController extends Controller
{
    public function show(
        Wedding $wedding,
        InvitationSnapshot $snapshot,
        TemplateRegistry $registry,
    ): View|RedirectResponse {
        $this->authorize('view', $wedding);

        $wedding->load(['partners', 'events', 'draft.templateVersion.template']);

        $draft = $wedding->draft;
        $templateVersion = $draft?->templateVersion;

        if ($draft === null || $templateVersion === null || ! $registry->supports($templateVersion->renderer_key)) {
            return redirect()
                ->route('weddings.edit', $wedding)
                ->withErrors(['preview' => 'Draf belum siap dipratinjau.']);
        }

        $invitation = PublicInvitation::fromSnapshot(
            $wedding->slug,
            $snapshot->build($wedding, $draft, $templateVersion, now(), null),
        );

        return view($registry->viewFor($templateVersion->renderer_key), [
            'invitation' => $invitation,
            'preview' => true,
        ]);
    }
}
