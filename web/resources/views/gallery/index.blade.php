@extends('layouts.app')

@section('title', 'Galeri')

@section('content')
    <div class="space-y-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <a href="{{ route('weddings.show', $wedding) }}" class="text-sm text-stone-500 hover:underline">&larr; Kembali ke undangan</a>
                <h1 class="mt-1 font-serif text-2xl font-semibold">Galeri</h1>
                <p class="text-sm text-stone-600">{{ $wedding->internal_title }}</p>
            </div>

            @if ($publicGalleryUrl !== null)
                <a href="{{ $publicGalleryUrl }}" target="_blank" rel="noopener"
                   class="rounded-md border border-stone-300 px-3 py-1.5 text-sm text-stone-700 hover:bg-white">
                    Lihat galeri publik
                </a>
            @endif
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-lg border border-stone-200 bg-white p-4">
                <p class="text-xs uppercase tracking-wide text-stone-500">Foto tersimpan</p>
                <p class="mt-1 text-2xl font-semibold">
                    {{ $stats['photos'] }}
                    <span class="text-sm font-normal text-stone-500">
                        / {{ $stats['photoLimit'] === null ? 'tanpa batas' : $stats['photoLimit'] }}
                    </span>
                </p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white p-4">
                <p class="text-xs uppercase tracking-wide text-stone-500">Penyimpanan</p>
                <p class="mt-1 text-2xl font-semibold">
                    {{ number_format($stats['bytes'] / 1048576, 1) }} MB
                    <span class="text-sm font-normal text-stone-500">
                        / {{ $stats['bytesLimit'] === null ? 'tanpa batas' : number_format($stats['bytesLimit'] / 1048576, 0).' MB' }}
                    </span>
                </p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white p-4">
                <p class="text-xs uppercase tracking-wide text-stone-500">Arsip siap diunduh</p>
                <p class="mt-1 text-2xl font-semibold">{{ $stats['readyArchives'] }}</p>
            </div>
        </div>

        <section class="rounded-lg border border-stone-200 bg-white p-5">
            <h2 class="font-serif text-lg font-semibold">Unggah foto</h2>
            <form method="POST" action="{{ route('weddings.gallery.photos.store', $wedding) }}"
                  enctype="multipart/form-data" class="mt-3 flex flex-wrap items-end gap-3"
                  data-upload-form
                  data-max-files="{{ $limits->maxFiles() }}"
                  data-max-file-bytes="{{ $limits->fileBytes() }}"
                  data-max-total-bytes="{{ $limits->requestBytes() }}">
                @csrf
                <div class="w-full flex-1 sm:min-w-56">
                    <label for="photos" class="block text-sm font-medium text-stone-700">Berkas foto</label>
                    <input id="photos" name="photos[]" type="file" multiple required
                           accept="image/jpeg,image/png,image/webp"
                           class="mt-1 block w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-sm">
                    <p class="mt-1 text-xs text-stone-500" data-upload-summary></p>
                </div>
                <div>
                    <label for="album_id" class="block text-sm font-medium text-stone-700">Album</label>
                    <select id="album_id" name="album_id" class="mt-1 rounded-md border border-stone-300 bg-white px-3 py-2 text-sm">
                        <option value="">Tanpa album</option>
                        @foreach ($albums as $album)
                            <option value="{{ $album->id }}">{{ $album->title }} ({{ $album->visibility->label() }})</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="rounded-md bg-rose-800 px-4 py-2 text-sm font-medium text-white hover:bg-rose-900">
                    Unggah
                </button>
            </form>
            <p class="mt-2 hidden rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-900"
               data-upload-error role="alert"></p>
            <p class="mt-2 text-xs text-stone-500">
                JPG, PNG, atau WebP. Maksimal {{ $limits->fileMb() }} MB per foto, {{ $limits->maxFiles() }} foto,
                dan {{ $limits->requestMb() }} MB sekali kirim.
                @if ($limits->clampedByPhp())
                    Angka ini mengikuti batas server; unggah beberapa kali bila fotonya besar.
                @endif
                Metadata EXIF dihapus otomatis. Hanya foto berstatus siap yang tampil di galeri publik.
            </p>
        </section>

        <section class="rounded-lg border border-stone-200 bg-white p-5">
            <h2 class="font-serif text-lg font-semibold">Album</h2>
            <form method="POST" action="{{ route('weddings.gallery.albums.store', $wedding) }}" class="mt-3 flex flex-wrap items-end gap-3">
                @csrf
                <div class="w-full flex-1 sm:min-w-48">
                    <label for="album-title" class="block text-sm font-medium text-stone-700">Nama album</label>
                    <input id="album-title" name="title" type="text" maxlength="80" required
                           class="mt-1 block w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label for="album-visibility" class="block text-sm font-medium text-stone-700">Visibilitas</label>
                    <select id="album-visibility" name="visibility" class="mt-1 rounded-md border border-stone-300 bg-white px-3 py-2 text-sm">
                        <option value="private">Privat</option>
                        <option value="public">Publik</option>
                    </select>
                </div>
                <button type="submit" class="rounded-md border border-stone-300 px-4 py-2 text-sm hover:bg-stone-50">
                    Buat album
                </button>
            </form>

            <ul class="mt-4 divide-y divide-stone-100">
                @forelse ($albums as $album)
                    <li class="flex flex-wrap items-center justify-between gap-3 py-3 text-sm">
                        <div>
                            <p class="font-medium">{{ $album->title }}</p>
                            <p class="text-xs text-stone-500">
                                {{ $album->assets_count }} foto ·
                                <span class="{{ $album->isPublic() ? 'text-emerald-700' : 'text-stone-500' }}">{{ $album->visibility->label() }}</span>
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <form method="POST" action="{{ route('weddings.gallery.albums.update', [$wedding, $album]) }}" class="flex items-center gap-2">
                                @csrf
                                @method('PATCH')
                                <select name="visibility" class="rounded-md border border-stone-300 bg-white px-2 py-1 text-xs">
                                    <option value="private" @selected($album->visibility->value === 'private')>Privat</option>
                                    <option value="public" @selected($album->visibility->value === 'public')>Publik</option>
                                </select>
                                <button type="submit" class="text-xs text-stone-600 underline-offset-4 hover:underline">Simpan</button>
                            </form>
                            <form method="POST" action="{{ route('weddings.gallery.albums.destroy', [$wedding, $album]) }}"
                                  onsubmit="return confirm('Hapus album ini? Foto tetap tersimpan tanpa album.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-rose-700 underline-offset-4 hover:underline">Hapus</button>
                            </form>
                        </div>
                    </li>
                @empty
                    <li class="py-3 text-sm text-stone-500">Belum ada album. Foto tanpa album tetap tampil di galeri privat.</li>
                @endforelse
            </ul>
        </section>

        <section class="space-y-4">
            <div class="flex flex-wrap items-center gap-2 text-sm">
                <a href="{{ route('weddings.gallery.index', $wedding) }}"
                   class="rounded-full border px-3 py-1 {{ $albumId === '' ? 'border-rose-800 bg-rose-50 text-rose-900' : 'border-stone-300 text-stone-600' }}">
                    Semua
                </a>
                @foreach ($albums as $album)
                    <a href="{{ route('weddings.gallery.index', ['wedding' => $wedding, 'album' => $album->id]) }}"
                       class="rounded-full border px-3 py-1 {{ $albumId === $album->id ? 'border-rose-800 bg-rose-50 text-rose-900' : 'border-stone-300 text-stone-600' }}">
                        {{ $album->title }}
                    </a>
                @endforeach
            </div>

            @if ($photos->isEmpty())
                <div class="rounded-lg border border-dashed border-stone-300 bg-white p-8 text-center text-sm text-stone-500">
                    Belum ada foto pada tampilan ini.
                </div>
            @else
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($photos as $photo)
                        <figure class="overflow-hidden rounded-lg border border-stone-200 bg-white">
                            <a href="{{ $previewUrls[$photo->id] }}" target="_blank" rel="noopener">
                                <img src="{{ $thumbUrls[$photo->id] }}"
                                     srcset="{{ $thumbUrls[$photo->id] }} 400w, {{ $previewUrls[$photo->id] }} 1280w"
                                     sizes="(min-width: 1024px) 25vw, (min-width: 640px) 50vw, 100vw"
                                     alt="{{ $photo->original_filename }}"
                                     loading="lazy" decoding="async" class="h-40 w-full object-cover">
                            </a>
                            <figcaption class="space-y-2 p-3 text-xs">
                                <p class="truncate font-medium" title="{{ $photo->original_filename }}">{{ $photo->original_filename }}</p>
                                <p class="text-stone-500">
                                    {{ number_format($photo->byte_size / 1024, 0) }} KB ·
                                    {{ $photo->album?->title ?? 'Tanpa album' }} ·
                                    {{ $photo->status->label() }}
                                </p>
                                <form method="POST" action="{{ route('weddings.gallery.photos.destroy', [$wedding, $photo]) }}"
                                      onsubmit="return confirm('Hapus foto ini secara permanen?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-700 underline-offset-4 hover:underline">Hapus</button>
                                </form>
                            </figcaption>
                        </figure>
                    @endforeach
                </div>

                {{ $photos->links() }}
            @endif
        </section>

        <section class="rounded-lg border border-stone-200 bg-white p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-serif text-lg font-semibold">Arsip ZIP</h2>
                    <p class="text-xs text-stone-500">
                        Berisi semua foto berstatus siap. Tautan unduhan berlaku sementara, berkas dihapus otomatis setelah masa retensi.
                    </p>
                </div>
                <form method="POST" action="{{ route('weddings.gallery.archives.store', $wedding) }}">
                    @csrf
                    <button type="submit" class="rounded-md bg-stone-900 px-4 py-2 text-sm font-medium text-white hover:bg-stone-800">
                        Buat arsip
                    </button>
                </form>
            </div>

            <ul class="mt-4 divide-y divide-stone-100 text-sm">
                @forelse ($archives as $archive)
                    <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                        <div>
                            <p class="font-medium">{{ $archive->status->label() }}</p>
                            <p class="text-xs text-stone-500">
                                {{ $archive->asset_count }} foto · {{ number_format($archive->byte_size / 1048576, 1) }} MB
                                @if ($archive->expires_at !== null)
                                    · berlaku sampai {{ $archive->expires_at->translatedFormat('d M Y H:i') }}
                                @endif
                            </p>
                            @if ($archive->error_message !== null)
                                <p class="text-xs text-rose-700">{{ $archive->error_message }}</p>
                            @endif
                        </div>
                        <div class="flex flex-wrap items-center gap-3 text-xs">
                            @if ($archive->isDownloadable())
                                <a href="{{ $archiveUrls[$archive->id] }}" class="text-rose-800 underline-offset-4 hover:underline">Unduh</a>
                            @endif
                            @if ($archive->status->canRetry())
                                <form method="POST" action="{{ route('weddings.gallery.archives.retry', [$wedding, $archive]) }}">
                                    @csrf
                                    <button type="submit" class="text-stone-600 underline-offset-4 hover:underline">Buat ulang</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('weddings.gallery.archives.destroy', [$wedding, $archive]) }}"
                                  onsubmit="return confirm('Hapus arsip ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-rose-700 underline-offset-4 hover:underline">Hapus</button>
                            </form>
                        </div>
                    </li>
                @empty
                    <li class="py-3 text-sm text-stone-500">Belum ada arsip.</li>
                @endforelse
            </ul>
        </section>
    </div>
@endsection
