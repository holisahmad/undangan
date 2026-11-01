<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Guests\IssueGuestInvitationLink;
use App\Actions\Guests\RevokeGuestInvitationLink;
use App\Models\Guest;
use App\Models\Wedding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class GuestInvitationLinkController extends Controller
{
    /**
     * The URL is flashed once; only the hash is stored, so it cannot be shown
     * again from the database (docs/DATABASE_SCHEMA.md, section 6).
     */
    public function store(
        Request $request,
        Wedding $wedding,
        Guest $guest,
        IssueGuestInvitationLink $issueLink,
    ): RedirectResponse {
        $this->authorize('update', $wedding);

        $result = $issueLink->handle($guest, $request->user());

        return redirect()
            ->route('weddings.guests.index', $wedding)
            ->with('status', 'Tautan personal untuk '.$guest->display_name.' dibuat. Salin sekarang; tautan tidak dapat ditampilkan lagi.')
            ->with('guest_link', route('guests.invitation.show', ['token' => $result['token']]));
    }

    public function destroy(
        Request $request,
        Wedding $wedding,
        Guest $guest,
        RevokeGuestInvitationLink $revokeLink,
    ): RedirectResponse {
        $this->authorize('update', $wedding);

        $revoked = $revokeLink->handle($guest, $request->user());

        return redirect()
            ->route('weddings.guests.index', $wedding)
            ->with('status', $revoked > 0
                ? 'Tautan personal dicabut dan tidak dapat dibuka lagi.'
                : 'Tidak ada tautan aktif untuk tamu ini.');
    }
}
