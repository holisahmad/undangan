<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Media\UploadMediaAsset;
use App\Enums\AlbumVisibility;
use App\Enums\MediaStatus;
use App\Http\Requests\StoreMediaRequest;
use App\Models\MediaAlbum;
use App\Models\MediaAsset;
use App\Models\Wedding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

/**
 * The couple's own pre-wedding photos inside the invitation: upload, choose the
 * cover and switch the invitation to an initials monogram. Uploads land in the
 * wedding's public "Prewedding" album, which is the one album the invitation and
 * the public gallery both read, so the couple manages them in one place.
 */
final class WeddingPhotoController extends Controller
{
    public const ALBUM_TITLE = 'Prewedding';

    public function store(StoreMediaRequest $request, Wedding $wedding, UploadMediaAsset $upload): RedirectResponse
    {
        $album = $this->album($wedding);
        $first = null;
        $uploaded = 0;

        /** @var array<int, UploadedFile> $files */
        $files = $request->file('photos', []);

        foreach ($files as $file) {
            $asset = $upload->handle($wedding, $request->user(), $file, $album);
            $first ??= $asset;
            $uploaded++;
        }

        // The first photo a couple uploads is the one they expect to see on the
        // cover, so it becomes the cover until they pick another.
        if ($wedding->cover_media_asset_id === null && $first !== null) {
            $wedding->forceFill(['cover_media_asset_id' => $first->getKey()])->save();
        }

        return back()->with('status', "{$uploaded} foto pre-wedding siap tampil di undangan.");
    }

    public function cover(Request $request, Wedding $wedding, MediaAsset $mediaAsset): RedirectResponse
    {
        $this->authorize('update', $wedding);

        abort_unless($mediaAsset->wedding_id === $wedding->getKey(), 404);

        if ($mediaAsset->status !== MediaStatus::Ready) {
            return back()->withErrors(['photos' => 'Foto ini belum siap dipakai sebagai sampul.']);
        }

        $wedding->forceFill(['cover_media_asset_id' => $mediaAsset->getKey()])->save();

        return back()->with('status', 'Foto sampul undangan diperbarui.');
    }

    public function display(Request $request, Wedding $wedding): RedirectResponse
    {
        $this->authorize('update', $wedding);

        $validated = $request->validate([
            'photo_display' => [
                'required',
                Rule::in([Wedding::PHOTO_DISPLAY_PREWEDDING, Wedding::PHOTO_DISPLAY_MONOGRAM]),
            ],
        ]);

        $wedding->forceFill(['photo_display' => $validated['photo_display']])->save();

        return back()->with('status', $validated['photo_display'] === Wedding::PHOTO_DISPLAY_MONOGRAM
            ? 'Undangan memakai monogram inisial, tanpa foto.'
            : 'Undangan menampilkan foto pre-wedding.');
    }

    private function album(Wedding $wedding): MediaAlbum
    {
        return MediaAlbum::query()->firstOrCreate(
            ['wedding_id' => $wedding->getKey(), 'title' => self::ALBUM_TITLE],
            ['visibility' => AlbumVisibility::Public],
        );
    }
}
