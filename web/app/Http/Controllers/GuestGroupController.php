<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreGuestGroupRequest;
use App\Models\GuestGroup;
use App\Models\Wedding;
use Illuminate\Http\RedirectResponse;

final class GuestGroupController extends Controller
{
    public function store(StoreGuestGroupRequest $request, Wedding $wedding): RedirectResponse
    {
        $wedding->guestGroups()->create([
            'name' => trim((string) $request->input('name')),
        ]);

        return redirect()
            ->route('weddings.guests.index', $wedding)
            ->with('status', 'Grup tamu ditambahkan.');
    }

    /**
     * Guests of a removed group are kept and become ungrouped; the foreign key
     * uses nullOnDelete so no guest data is lost.
     */
    public function destroy(Wedding $wedding, GuestGroup $guestGroup): RedirectResponse
    {
        $this->authorize('update', $wedding);

        $guestGroup->delete();

        return redirect()
            ->route('weddings.guests.index', $wedding)
            ->with('status', 'Grup dihapus. Tamu di dalamnya tetap tersimpan tanpa grup.');
    }
}
