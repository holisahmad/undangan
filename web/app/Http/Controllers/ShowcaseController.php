<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Analytics\TrackEvent;
use App\Data\PublicInvitation;
use App\Models\AnalyticsEvent;
use App\Models\Wedding;
use App\Support\Templates\TemplateRegistry;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Opt-in portfolio. A couple must enable showcase explicitly and the wedding
 * must still have an active publication; everything else stays private and
 * noindex like a normal invitation.
 */
final class ShowcaseController extends Controller
{
    public function index(): View
    {
        $weddings = Wedding::query()
            ->where('showcase_enabled', true)
            ->whereHas('activePublication')
            ->with('activePublication')
            ->latest('showcase_enabled_at')
            ->limit((int) config('platform.marketing.showcase_limit', 24))
            ->get();

        // Cards are built from the frozen snapshot, exactly like the page they
        // link to, so the list can never show newer draft data.
        $cards = $weddings
            ->filter(static fn (Wedding $wedding): bool => $wedding->activePublication !== null)
            ->map(static function (Wedding $wedding): array {
                $invitation = PublicInvitation::fromSnapshot(
                    $wedding->slug,
                    $wedding->activePublication->published_snapshot,
                );

                $firstEvent = $invitation->events[0] ?? [];

                return [
                    'headline' => $invitation->coupleHeadline() ?: 'Undangan pernikahan',
                    'template' => $invitation->templateName,
                    'when' => $firstEvent['starts_label'] ?? null,
                    'venue' => $firstEvent['venue_name'] ?? null,
                    'url' => route('showcase.show', ['slug' => $wedding->slug]),
                ];
            })
            ->values()
            ->all();

        return view('showcase.index', [
            'cards' => $cards,
        ]);
    }

    public function show(string $slug, TemplateRegistry $registry, TrackEvent $track): View
    {
        $wedding = Wedding::query()
            ->where('slug', $slug)
            ->where('showcase_enabled', true)
            ->first();

        abort_if($wedding === null, 404);

        $publication = $wedding->activePublication()->first();

        abort_if($publication === null, 404);

        $invitation = PublicInvitation::fromSnapshot($wedding->slug, $publication->published_snapshot);

        try {
            $view = $registry->viewFor($invitation->rendererKey);
        } catch (InvalidArgumentException) {
            Log::warning('Showcase uses an unavailable renderer.', [
                'wedding_id' => $wedding->getKey(),
            ]);

            abort(404);
        }

        $track->handle(AnalyticsEvent::INVITATION_VIEWED, $wedding, ['surface' => 'showcase']);

        return view($view, [
            'invitation' => $invitation,
            'preview' => false,
            'guest' => null,
            'rsvp' => null,
            'token' => null,
            'hasGallery' => false,
            'galleryUrl' => null,
            'demo' => false,
            'showcase' => true,
            'showcaseUrl' => route('showcase.show', ['slug' => $wedding->slug]),
        ]);
    }
}
