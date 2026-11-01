<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Media\CreateMediaAlbum;
use App\Actions\Media\DeleteMediaAlbum;
use App\Enums\AlbumVisibility;
use App\Http\Requests\StoreMediaAlbumRequest;
use App\Http\Requests\UpdateMediaAlbumRequest;
use App\Models\MediaAlbum;
use App\Models\Wedding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class MediaAlbumController extends Controller
{
    public function store(StoreMediaAlbumRequest $request, Wedding $wedding, CreateMediaAlbum $create): RedirectResponse
    {
        $create->handle(
            $wedding,
            $request->user(),
            $request->string('title')->toString(),
            AlbumVisibility::from($request->string('visibility')->toString()),
        );

        return back()->with('status', 'Album dibuat.');
    }

    public function update(UpdateMediaAlbumRequest $request, Wedding $wedding, MediaAlbum $mediaAlbum): RedirectResponse
    {
        $mediaAlbum->update([
            'visibility' => AlbumVisibility::from($request->string('visibility')->toString()),
        ]);

        if ($request->filled('title')) {
            $mediaAlbum->update(['title' => trim($request->string('title')->toString())]);
        }

        return back()->with('status', 'Album diperbarui.');
    }

    public function destroy(Request $request, Wedding $wedding, MediaAlbum $mediaAlbum, DeleteMediaAlbum $delete): RedirectResponse
    {
        $this->authorize('update', $wedding);

        $delete->handle($mediaAlbum, $request->user());

        return back()->with('status', 'Album dihapus; fotonya tetap tersimpan tanpa album.');
    }
}
