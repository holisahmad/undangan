<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\PublicInvitation;
use App\Models\Wedding;
use App\Support\Templates\TemplateRegistry;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * The only unauthenticated read path. It never touches live tables other than
 * the slug lookup: every field comes from the stored publication snapshot
 * (docs/PRD.md, section 7.3).
 */
final class PublicInvitationController extends Controller
{
    public function show(string $slug, TemplateRegistry $registry): View|Response
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

        $invitation = PublicInvitation::fromSnapshot($wedding->slug, $publication->published_snapshot);

        try {
            $view = $registry->viewFor($invitation->rendererKey);
        } catch (InvalidArgumentException) {
            Log::warning('Publication uses an unavailable renderer.', [
                'wedding_id' => $wedding->getKey(),
                'renderer_key' => $invitation->rendererKey,
            ]);

            return response()->view('invitations.unavailable', ['reason' => 'unavailable'], 410);
        }

        return view($view, [
            'invitation' => $invitation,
            'preview' => false,
        ]);
    }
}
