<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Guests\SendRsvpReminders;
use App\Models\Wedding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Owner-triggered RSVP reminder run. The scheduled command uses the same
 * action; both respect the cooldown and only reach guests with an address.
 */
final class GuestReminderController extends Controller
{
    public function store(Request $request, Wedding $wedding, SendRsvpReminders $reminders): RedirectResponse
    {
        $this->authorize('update', $wedding);

        $result = $reminders->handle($wedding, $request->user());

        if ($result['unavailable']) {
            return redirect()
                ->route('weddings.guests.index', $wedding)
                ->withErrors(['reminders' => 'Terbitkan undangan terlebih dahulu sebelum mengirim pengingat RSVP.']);
        }

        if ($result['eligible'] === 0) {
            return redirect()
                ->route('weddings.guests.index', $wedding)
                ->with('status', 'Tidak ada tamu yang perlu diingatkan. Pengingat hanya untuk tamu yang punya email, belum menjawab, dan belum diingatkan dalam '
                    .config('platform.guests.reminder_cooldown_days', 7).' hari terakhir.');
        }

        $status = $result['sent'].' pengingat RSVP dikirim.';

        if ($result['failed'] > 0) {
            $status .= ' '.$result['failed'].' gagal dan dicoba lagi pada pengingat berikutnya.';
        }

        return redirect()->route('weddings.guests.index', $wedding)->with('status', $status);
    }
}
