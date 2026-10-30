<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\PartnerRequest;
use App\Models\Wedding;
use App\Models\WeddingPartner;
use Illuminate\Http\RedirectResponse;

final class WeddingPartnerController extends Controller
{
    public function store(PartnerRequest $request, Wedding $wedding): RedirectResponse
    {
        $wedding->partners()->create($request->payload());

        return $this->back($wedding, 'Data mempelai ditambahkan.');
    }

    public function update(PartnerRequest $request, Wedding $wedding, WeddingPartner $partner): RedirectResponse
    {
        $this->ensureBelongsTo($wedding, $partner);

        $partner->update($request->payload());

        return $this->back($wedding, 'Data mempelai diperbarui.');
    }

    public function destroy(Wedding $wedding, WeddingPartner $partner): RedirectResponse
    {
        $this->authorize('update', $wedding);
        $this->ensureBelongsTo($wedding, $partner);

        $partner->delete();

        return $this->back($wedding, 'Data mempelai dihapus.');
    }

    private function ensureBelongsTo(Wedding $wedding, WeddingPartner $partner): void
    {
        abort_unless((string) $partner->wedding_id === (string) $wedding->getKey(), 404);
    }

    private function back(Wedding $wedding, string $status): RedirectResponse
    {
        return redirect()->route('weddings.edit', $wedding)->with('status', $status);
    }
}
