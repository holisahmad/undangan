<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Analytics\TrackEvent;
use App\Actions\Guests\SubmitRsvp;
use App\Data\PublicInvitation;
use App\Enums\RsvpStatus;
use App\Models\AnalyticsEvent;
use App\Models\GuestInvitationLink;
use App\Support\Invitations\InvitationMedia;
use App\Support\Templates\TemplateRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Public personalised invitation. The token identifies one guest and is the
 * only credential; the page never lists other guests and never exposes contact
 * details (docs/PRD.md, section 7.3). The owner's chosen template is used, so
 * the design seen in the editor matches what guests receive.
 */
final class GuestInvitationController extends Controller
{
    public function show(Request $request, TemplateRegistry $registry, TrackEvent $track): View|Response
    {
        $link = $this->resolveLink($request);
        $guest = $link->guest()->with('rsvp')->firstOrFail();
        $wedding = $guest->wedding;

        $publication = $wedding->activePublication()->first();

        if ($publication === null) {
            $latest = $wedding->latestPublication()->first();

            abort_if($latest === null, 404);

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

        $link->registerView();

        $track->handle(AnalyticsEvent::INVITATION_VIEWED, $wedding, ['surface' => 'personal']);

        // Resolve the photos once here, exactly like the public page, so a
        // personal link renders the same cover photo and the same gallery link
        // without each renderer running its own query.
        $media = InvitationMedia::for($invitation, false, $wedding);

        return view($view, [
            'invitation' => $invitation,
            'preview' => false,
            'guest' => $guest,
            'rsvp' => $guest->rsvp,
            'token' => (string) $request->route('token'),
            'media' => $media,
            'hasGallery' => $media->gallery() !== [],
            'galleryUrl' => route('invitations.gallery', ['slug' => $wedding->slug]),
        ]);
    }

    public function rsvp(Request $request, SubmitRsvp $submitRsvp): RedirectResponse
    {
        $link = $this->resolveLink($request);
        $guest = $link->guest()->firstOrFail();

        // A response is meaningless while the invitation is not being served.
        abort_if($guest->wedding->activePublication()->first() === null, 410);

        $data = $request->validate([
            'status' => ['required', Rule::enum(RsvpStatus::class)],
            'party_size' => [
                Rule::requiredIf($request->input('status') === RsvpStatus::Attending->value),
                'nullable',
                'integer',
                'min:1',
                'max:'.$guest->allowed_party_size,
            ],
            'message' => ['nullable', 'string', 'max:500'],
            'show_message' => ['nullable', 'boolean'],
        ], [
            'party_size.required' => 'Jumlah peserta wajib diisi jika Anda hadir.',
            'party_size.max' => "Jumlah peserta melebihi kuota tamu ini ({$guest->allowed_party_size} orang).",
        ]);

        $submitRsvp->handle(
            $guest,
            RsvpStatus::from($data['status']),
            (int) ($data['party_size'] ?? 0),
            $data['message'] ?? null,
            $request->boolean('show_message'),
        );

        return redirect()
            ->route('guests.invitation.show', ['token' => $request->route('token')])
            ->with('rsvp_saved', true);
    }

    private function resolveLink(Request $request): GuestInvitationLink
    {
        $token = $request->route('token');
        $link = GuestInvitationLink::resolve(is_string($token) ? $token : null);

        abort_if($link === null, 404);

        return $link;
    }
}
