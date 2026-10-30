<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\EventRequest;
use App\Models\Wedding;
use App\Models\WeddingEvent;
use Illuminate\Http\RedirectResponse;

final class WeddingEventController extends Controller
{
    public function store(EventRequest $request, Wedding $wedding): RedirectResponse
    {
        $wedding->events()->create($request->payload());

        return $this->back($wedding, 'Acara ditambahkan.');
    }

    public function update(EventRequest $request, Wedding $wedding, WeddingEvent $event): RedirectResponse
    {
        $this->ensureBelongsTo($wedding, $event);

        $event->update($request->payload());

        return $this->back($wedding, 'Acara diperbarui.');
    }

    public function destroy(Wedding $wedding, WeddingEvent $event): RedirectResponse
    {
        $this->authorize('update', $wedding);
        $this->ensureBelongsTo($wedding, $event);

        $event->delete();

        return $this->back($wedding, 'Acara dihapus.');
    }

    private function ensureBelongsTo(Wedding $wedding, WeddingEvent $event): void
    {
        abort_unless((string) $event->wedding_id === (string) $wedding->getKey(), 404);
    }

    private function back(Wedding $wedding, string $status): RedirectResponse
    {
        return redirect()->route('weddings.edit', $wedding)->with('status', $status);
    }
}
