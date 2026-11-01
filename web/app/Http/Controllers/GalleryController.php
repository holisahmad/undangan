<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Media\BuildGalleryArchive;
use App\Actions\Media\DeleteMediaAsset;
use App\Actions\Media\UploadMediaAsset;
use App\Enums\ArchiveStatus;
use App\Enums\MediaStatus;
use App\Http\Requests\StoreMediaRequest;
use App\Models\ArchiveJob;
use App\Models\MediaAsset;
use App\Models\Wedding;
use App\Support\Audit\AuditLogger;
use App\Support\Media\MediaQuota;
use App\Support\Media\MediaStorage;
use App\Support\Media\UploadLimits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

final class GalleryController extends Controller
{
    public function index(Request $request, Wedding $wedding, MediaQuota $quota): View
    {
        $this->authorize('view', $wedding);

        $albumId = (string) $request->query('album', '');

        $photos = $wedding->mediaAssets()
            ->where('status', '!=', MediaStatus::Deleted)
            ->when($albumId !== '', static fn ($query) => $query->where('album_id', $albumId))
            ->with('album')
            ->latest()
            ->paginate(24)
            ->withQueryString();

        $ttl = (int) config('platform.media.signed_url_ttl_minutes', 15);
        $thumbUrls = [];
        $previewUrls = [];

        foreach ($photos as $photo) {
            $thumbUrls[$photo->getKey()] = URL::temporarySignedRoute(
                'media.photos.download',
                now()->addMinutes($ttl),
                ['mediaAsset' => $photo->getKey(), 'variant' => 'thumb'],
            );
            $previewUrls[$photo->getKey()] = URL::temporarySignedRoute(
                'media.photos.download',
                now()->addMinutes($ttl),
                ['mediaAsset' => $photo->getKey(), 'variant' => 'preview'],
            );
        }

        $archives = $wedding->archives()->latest()->limit(10)->get();

        return view('gallery.index', [
            'wedding' => $wedding,
            'photos' => $photos,
            'albums' => $wedding->mediaAlbums()
                ->withCount(['assets' => static fn ($query) => $query->where('status', '!=', MediaStatus::Deleted)])
                ->orderBy('title')
                ->get(),
            'albumId' => $albumId,
            'archives' => $archives,
            'archiveUrls' => $archives->mapWithKeys(static fn (ArchiveJob $archive): array => [
                $archive->getKey() => URL::temporarySignedRoute(
                    'media.archives.download',
                    now()->addMinutes($ttl),
                    ['archive' => $archive->getKey()],
                ),
            ])->all(),
            'stats' => [
                'photos' => $quota->photosUsed($wedding),
                'photoLimit' => $quota->photoLimit($wedding),
                'bytes' => $quota->bytesUsed($wedding),
                'bytesLimit' => $quota->bytesLimit($wedding),
                'readyArchives' => $wedding->archives()
                    ->where('status', ArchiveStatus::Ready)
                    ->where(static fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                    ->count(),
            ],
            'thumbUrls' => $thumbUrls,
            'previewUrls' => $previewUrls,
            'publicGalleryUrl' => $wedding->activePublication()->exists()
                ? route('invitations.gallery', $wedding->slug)
                : null,
            // The effective ceilings: product config lowered by PHP's own
            // upload_max_filesize/post_max_size, so the form and the server
            // agree on what will actually be accepted.
            'limits' => UploadLimits::current(),
        ]);
    }

    public function store(StoreMediaRequest $request, Wedding $wedding, UploadMediaAsset $upload): RedirectResponse
    {
        $album = null;

        if ($request->filled('album_id')) {
            $album = $wedding->mediaAlbums()->whereKey($request->string('album_id')->toString())->first();
        }

        $uploaded = 0;

        /** @var array<int, UploadedFile> $files */
        $files = $request->file('photos', []);

        foreach ($files as $file) {
            $upload->handle($wedding, $request->user(), $file, $album);
            $uploaded++;
        }

        return back()->with('status', "{$uploaded} foto diunggah dan siap tampil.");
    }

    public function destroy(Request $request, Wedding $wedding, MediaAsset $mediaAsset, DeleteMediaAsset $delete): RedirectResponse
    {
        $this->authorize('update', $wedding);

        $delete->handle($mediaAsset, $request->user());

        return back()->with('status', 'Foto dihapus.');
    }

    public function storeArchive(Request $request, Wedding $wedding, BuildGalleryArchive $build): RedirectResponse
    {
        $this->authorize('update', $wedding);

        $archive = $build->handle($wedding, $request->user());

        if ($archive->status !== ArchiveStatus::Ready) {
            return back()->withErrors([
                'archive' => $archive->error_message ?? 'Arsip gagal dibuat.',
            ]);
        }

        return back()->with('status', 'Arsip siap diunduh selama '
            .((int) config('platform.media.archive_retention_days', 7)).' hari.');
    }

    public function retryArchive(Request $request, Wedding $wedding, ArchiveJob $archive, BuildGalleryArchive $build): RedirectResponse
    {
        $this->authorize('update', $wedding);

        $archive = $build->retry($archive, $request->user());

        if ($archive->status !== ArchiveStatus::Ready) {
            return back()->withErrors([
                'archive' => $archive->error_message ?? 'Arsip gagal dibuat ulang.',
            ]);
        }

        return back()->with('status', 'Arsip berhasil dibuat ulang.');
    }

    public function destroyArchive(Wedding $wedding, ArchiveJob $archive, MediaStorage $storage, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('update', $wedding);

        $storage->delete($archive->object_key);

        $audit->log(
            action: 'media.archive.deleted',
            resourceType: 'archive_job',
            resourceId: $archive->getKey(),
            actor: request()->user(),
            metadata: ['wedding_id' => $wedding->getKey()],
        );

        $archive->delete();

        return back()->with('status', 'Arsip dihapus.');
    }
}
