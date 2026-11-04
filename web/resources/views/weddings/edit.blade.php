@extends('layouts.app')

@section('title', 'Edit ' . $wedding->internal_title)

@section('content')
    @php
        $content = $draft?->content_json ?? [];
        $active = $wedding->activePublication;
        $inputClass = 'mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-rose-700 focus:outline-none focus:ring-1 focus:ring-rose-700';
        $labelClass = 'block text-sm font-medium';
    @endphp

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="{{ route('weddings.show', $wedding) }}" class="text-sm text-stone-500 hover:underline">&larr; Ringkasan undangan</a>
            <h1 class="mt-2 font-serif text-2xl font-semibold">Edit undangan</h1>
            <p class="mt-1 text-sm text-stone-600">
                {{ $wedding->internal_title }} &middot; {{ $wedding->lifecycle_status->label() }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('weddings.preview', $wedding) }}"
               class="rounded-md border border-stone-300 bg-white px-4 py-2 text-sm font-medium hover:border-stone-400">
                Pratinjau
            </a>
            <a href="{{ route('weddings.show', $wedding) }}"
               class="rounded-md bg-rose-800 px-4 py-2 text-sm font-medium text-white hover:bg-rose-900">
                Terbitkan
            </a>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="rounded-lg border border-stone-200 bg-white p-5">
                <h2 class="font-serif text-lg font-semibold">Nama internal</h2>
                <form method="POST" action="{{ route('weddings.update', $wedding) }}" class="mt-3 flex flex-wrap items-end gap-3">
                    @csrf
                    @method('PATCH')
                    <div class="w-full flex-1 sm:min-w-[16rem]">
                        <label for="internal_title" class="{{ $labelClass }}">Nama internal</label>
                        <input id="internal_title" name="internal_title" type="text" required maxlength="160"
                               value="{{ old('internal_title', $wedding->internal_title) }}" class="{{ $inputClass }}">
                    </div>
                    <button type="submit" class="rounded-md border border-stone-300 bg-white px-4 py-2.5 text-sm font-medium hover:border-stone-400 sm:py-2">
                        Simpan nama
                    </button>
                </form>
            </section>

            <section class="rounded-lg border border-stone-200 bg-white p-5">
                <h2 class="font-serif text-lg font-semibold">Mempelai</h2>

                @foreach ($wedding->partners as $partner)
                    <details class="mt-3 rounded-md border border-stone-200 p-3">
                        <summary class="cursor-pointer text-sm font-medium">
                            {{ ucfirst($partner->partner_type) }} &middot; {{ $partner->display_name }}
                        </summary>

                        <form method="POST" action="{{ route('weddings.partners.update', [$wedding, $partner]) }}" class="mt-3 grid gap-3 sm:grid-cols-2">
                            @csrf
                            @method('PATCH')
                            @include('weddings.partials.partner-fields', ['partner' => $partner, 'inputClass' => $inputClass, 'labelClass' => $labelClass])

                            <div class="sm:col-span-2 flex items-center justify-between gap-3">
                                <button type="submit" class="rounded-md bg-stone-900 px-4 py-2 text-sm font-medium text-white hover:bg-stone-800">
                                    Simpan
                                </button>
                            </div>
                        </form>

                        <form method="POST" action="{{ route('weddings.partners.destroy', [$wedding, $partner]) }}" class="mt-2"
                              onsubmit="return confirm('Hapus data mempelai ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-rose-800 underline-offset-4 hover:underline">Hapus</button>
                        </form>
                    </details>
                @endforeach

                <details class="mt-4 rounded-md border border-dashed border-stone-300 p-3">
                    <summary class="cursor-pointer text-sm font-medium">Tambah mempelai</summary>

                    <form method="POST" action="{{ route('weddings.partners.store', $wedding) }}" class="mt-3 grid gap-3 sm:grid-cols-2">
                        @csrf
                        @include('weddings.partials.partner-fields', ['partner' => null, 'inputClass' => $inputClass, 'labelClass' => $labelClass])

                        <div class="sm:col-span-2">
                            <button type="submit" class="rounded-md bg-stone-900 px-4 py-2 text-sm font-medium text-white hover:bg-stone-800">
                                Tambah
                            </button>
                        </div>
                    </form>
                </details>
            </section>

            <section class="rounded-lg border border-stone-200 bg-white p-5">
                <h2 class="font-serif text-lg font-semibold">Acara</h2>

                @foreach ($wedding->events as $event)
                    <details class="mt-3 rounded-md border border-stone-200 p-3">
                        <summary class="cursor-pointer text-sm font-medium">
                            {{ $event->title }} &middot;
                            {{ $event->starts_at->copy()->setTimezone($event->timezone)->locale(app()->getLocale())->translatedFormat('d F Y · H:i') }}
                        </summary>

                        <form method="POST" action="{{ route('weddings.events.update', [$wedding, $event]) }}" class="mt-3 grid gap-3 sm:grid-cols-2">
                            @csrf
                            @method('PATCH')
                            @include('weddings.partials.event-fields', ['event' => $event, 'inputClass' => $inputClass, 'labelClass' => $labelClass])

                            <div class="sm:col-span-2">
                                <button type="submit" class="rounded-md bg-stone-900 px-4 py-2 text-sm font-medium text-white hover:bg-stone-800">
                                    Simpan
                                </button>
                            </div>
                        </form>

                        <form method="POST" action="{{ route('weddings.events.destroy', [$wedding, $event]) }}" class="mt-2"
                              onsubmit="return confirm('Hapus acara ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-rose-800 underline-offset-4 hover:underline">Hapus</button>
                        </form>
                    </details>
                @endforeach

                <details class="mt-4 rounded-md border border-dashed border-stone-300 p-3">
                    <summary class="cursor-pointer text-sm font-medium">Tambah acara</summary>

                    <form method="POST" action="{{ route('weddings.events.store', $wedding) }}" class="mt-3 grid gap-3 sm:grid-cols-2">
                        @csrf
                        @include('weddings.partials.event-fields', ['event' => null, 'inputClass' => $inputClass, 'labelClass' => $labelClass])

                        <div class="sm:col-span-2">
                            <button type="submit" class="rounded-md bg-stone-900 px-4 py-2 text-sm font-medium text-white hover:bg-stone-800">
                                Tambah
                            </button>
                        </div>
                    </form>
                </details>
            </section>

            <section class="rounded-lg border border-stone-200 bg-white p-5">
                <h2 class="font-serif text-lg font-semibold">Isi undangan</h2>
                <p class="mt-1 text-sm text-stone-600">
                    Teks di bawah ini hanya tampil di halaman publik setelah diterbitkan ulang.
                </p>

                <form method="POST" action="{{ route('weddings.draft.update', $wedding) }}"
                      data-autosave-url="{{ route('weddings.draft.update', $wedding) }}"
                      data-autosave-status="#draft-save-status"
                      class="mt-4 space-y-4">
                    @csrf
                    @method('PUT')

                    @php
                        $draftVersion = (string) ($draft?->template_version_id ?? '');
                        $draftLock = $locked[$draftVersion] ?? null;
                    @endphp

                    <div>
                        <label for="template_version_id" class="{{ $labelClass }}">Template</label>
                        <select id="template_version_id" name="template_version_id" class="{{ $inputClass }}">
                            @foreach ($templates as $version)
                                @php $lock = $locked[(string) $version->getKey()] ?? null; @endphp
                                <option value="{{ $version->getKey() }}"
                                    @disabled($lock !== null)
                                    @selected(old('template_version_id', $draft?->template_version_id) === $version->getKey())>
                                    {{ $version->template->name }} (versi {{ $version->version }}){{ $lock === null ? '' : ' — '.$lock }}
                                </option>
                            @endforeach
                        </select>
                        @if ($locked !== [])
                            <p class="mt-1 text-xs text-stone-500">
                                Tema bertanda paket dapat dilihat sebagai contoh, tetapi baru bisa dipilih setelah paket
                                tersebut aktif pada pesanan Anda.
                            </p>
                        @endif
                        @if ($draftLock !== null)
                            {{-- The draft keeps its design because it was chosen while the choice was
                                 free; only publication refuses it (PublishInvitation). --}}
                            <div class="mt-2 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs leading-relaxed text-amber-900">
                                Tema draf ini hanya tersedia pada {{ $draftLock }}. Isi undangan tetap bisa disunting,
                                tetapi terbitkan ulang setelah paket yang sesuai aktif, atau ganti tema terlebih dahulu.
                            </div>
                        @endif
                    </div>

                    <div>
                        <label for="cover_title" class="{{ $labelClass }}">Judul pembuka</label>
                        <input id="cover_title" name="content[cover][title]" type="text" required maxlength="120"
                               value="{{ old('content.cover.title', data_get($content, 'cover.title', 'The Wedding Of')) }}"
                               class="{{ $inputClass }}">
                    </div>

                    <div>
                        <label for="cover_greeting" class="{{ $labelClass }}">Salam pembuka (opsional)</label>
                        <input id="cover_greeting" name="content[cover][greeting]" type="text" maxlength="160"
                               value="{{ old('content.cover.greeting', data_get($content, 'cover.greeting')) }}"
                               class="{{ $inputClass }}">
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="cover_quote" class="{{ $labelClass }}">Kutipan (opsional)</label>
                            <textarea id="cover_quote" name="content[cover][quote]" rows="3" maxlength="300"
                                      class="{{ $inputClass }}">{{ old('content.cover.quote', data_get($content, 'cover.quote')) }}</textarea>
                        </div>
                        <div>
                            <label for="cover_quote_source" class="{{ $labelClass }}">Sumber kutipan (opsional)</label>
                            <input id="cover_quote_source" name="content[cover][quote_source]" type="text" maxlength="120"
                                   value="{{ old('content.cover.quote_source', data_get($content, 'cover.quote_source')) }}"
                                   class="{{ $inputClass }}">
                        </div>
                    </div>

                    <div>
                        <label for="story_heading" class="{{ $labelClass }}">Judul cerita (opsional)</label>
                        <input id="story_heading" name="content[story][heading]" type="text" maxlength="80"
                               value="{{ old('content.story.heading', data_get($content, 'story.heading')) }}"
                               class="{{ $inputClass }}">
                    </div>

                    <div>
                        <label for="story_body" class="{{ $labelClass }}">Cerita (opsional)</label>
                        <textarea id="story_body" name="content[story][body]" rows="5" maxlength="2000"
                                  class="{{ $inputClass }}">{{ old('content.story.body', data_get($content, 'story.body')) }}</textarea>
                    </div>

                    <fieldset class="rounded-md border border-stone-200 p-3">
                        <legend class="px-1 text-sm font-medium">Bagian yang ditampilkan</legend>
                        @foreach (['show_story' => 'Cerita', 'show_events' => 'Acara', 'show_map' => 'Peta', 'show_gift' => 'Hadiah'] as $key => $label)
                            <label class="mt-2 flex items-center gap-2 text-sm">
                                <input type="hidden" name="content[features][{{ $key }}]" value="0">
                                <input type="checkbox" name="content[features][{{ $key }}]" value="1"
                                       @checked((string) old('content.features.' . $key, data_get($content, 'features.' . $key, true)) === '1')
                                       class="rounded border-stone-300 text-rose-800 focus:ring-rose-700">
                                Tampilkan {{ $label }}
                            </label>
                        @endforeach
                    </fieldset>

                    <fieldset class="rounded-md border border-stone-200 p-3">
                        <legend class="px-1 text-sm font-medium">Hadiah pernikahan</legend>
                        <p class="mt-1 text-xs text-stone-600">
                            Rekening dan alamat kado hanya tampil setelah diterbitkan ulang. Baris yang nomornya
                            kosong diabaikan.
                        </p>

                        <label class="mt-3 flex items-center gap-2 text-sm">
                            <input type="hidden" name="content[gift][enabled]" value="0">
                            <input type="checkbox" name="content[gift][enabled]" value="1"
                                   @checked((string) old('content.gift.enabled', data_get($content, 'gift.enabled', true)) === '1')
                                   class="rounded border-stone-300 text-rose-800 focus:ring-rose-700">
                            Tampilkan bagian hadiah
                        </label>

                        @for ($index = 0; $index < 2; $index++)
                            <div class="mt-3 grid gap-3 rounded-md border border-dashed border-stone-300 p-3 sm:grid-cols-3">
                                <div>
                                    <label for="gift_bank_{{ $index }}" class="{{ $labelClass }}">Bank {{ $index + 1 }}</label>
                                    <input id="gift_bank_{{ $index }}" name="content[gift][accounts][{{ $index }}][bank]" type="text" maxlength="40"
                                           value="{{ old('content.gift.accounts.' . $index . '.bank', data_get($content, 'gift.accounts.' . $index . '.bank')) }}"
                                           class="{{ $inputClass }}" placeholder="BCA">
                                </div>
                                <div>
                                    <label for="gift_number_{{ $index }}" class="{{ $labelClass }}">Nomor rekening</label>
                                    <input id="gift_number_{{ $index }}" name="content[gift][accounts][{{ $index }}][number]" type="text" maxlength="40"
                                           value="{{ old('content.gift.accounts.' . $index . '.number', data_get($content, 'gift.accounts.' . $index . '.number')) }}"
                                           class="{{ $inputClass }}" inputmode="numeric" placeholder="1234567890">
                                </div>
                                <div>
                                    <label for="gift_holder_{{ $index }}" class="{{ $labelClass }}">Atas nama</label>
                                    <input id="gift_holder_{{ $index }}" name="content[gift][accounts][{{ $index }}][holder]" type="text" maxlength="100"
                                           value="{{ old('content.gift.accounts.' . $index . '.holder', data_get($content, 'gift.accounts.' . $index . '.holder')) }}"
                                           class="{{ $inputClass }}">
                                </div>
                            </div>
                        @endfor

                        <div class="mt-3 grid gap-3">
                            <div>
                                <label for="gift_note" class="{{ $labelClass }}">Catatan (opsional)</label>
                                <textarea id="gift_note" name="content[gift][note]" rows="2" maxlength="400"
                                          class="{{ $inputClass }}">{{ old('content.gift.note', data_get($content, 'gift.note')) }}</textarea>
                            </div>
                            <div>
                                <label for="gift_address" class="{{ $labelClass }}">Alamat kirim kado (opsional)</label>
                                <input id="gift_address" name="content[gift][address]" type="text" maxlength="240"
                                       value="{{ old('content.gift.address', data_get($content, 'gift.address')) }}"
                                       class="{{ $inputClass }}">
                            </div>
                        </div>
                    </fieldset>

                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit" class="rounded-md bg-rose-800 px-4 py-2 text-sm font-medium text-white hover:bg-rose-900">
                            Simpan draft
                        </button>
                        <p id="draft-save-status" role="status" aria-live="polite" class="text-xs text-stone-500">
                            Perubahan disimpan otomatis saat Anda berhenti mengetik.
                        </p>
                    </div>
                </form>
            </section>

            <section id="foto-prewedding" class="rounded-lg border border-stone-200 bg-white p-5">
                <h2 class="font-serif text-lg font-semibold">Foto pre-wedding</h2>
                <p class="mt-1 text-sm text-stone-600">
                    Foto Anda sendiri menggantikan gambar contoh pada tema. Tanpa foto, undangan memakai monogram
                    inisial kedua mempelai.
                </p>

                <form method="POST" action="{{ route('weddings.photos.display', $wedding) }}" class="mt-4 space-y-3">
                    @csrf
                    @method('PATCH')

                    <fieldset class="space-y-2">
                        <legend class="{{ $labelClass }}">Tampilan undangan</legend>

                        <label class="flex items-start gap-2 text-sm">
                            <input type="radio" name="photo_display"
                                   value="{{ \App\Models\Wedding::PHOTO_DISPLAY_PREWEDDING }}"
                                   @checked($photoDisplay !== \App\Models\Wedding::PHOTO_DISPLAY_MONOGRAM)
                                   class="mt-0.5 border-stone-300 text-rose-800 focus:ring-rose-700">
                            <span>
                                <span class="font-medium">Pakai foto pre-wedding</span><br>
                                <span class="text-xs text-stone-500">Foto sampul tampil di bagian pembuka undangan.</span>
                            </span>
                        </label>

                        <label class="flex items-start gap-2 text-sm">
                            <input type="radio" name="photo_display"
                                   value="{{ \App\Models\Wedding::PHOTO_DISPLAY_MONOGRAM }}"
                                   @checked($photoDisplay === \App\Models\Wedding::PHOTO_DISPLAY_MONOGRAM)
                                   class="mt-0.5 border-stone-300 text-rose-800 focus:ring-rose-700">
                            <span>
                                <span class="font-medium">Tanpa foto &mdash; monogram inisial</span><br>
                                <span class="text-xs text-stone-500">Untuk yang belum punya foto pre-wedding.</span>
                            </span>
                        </label>
                    </fieldset>

                    <button type="submit" class="rounded-md border border-stone-300 px-3 py-1.5 text-sm font-medium text-stone-700 hover:bg-stone-50">
                        Simpan pilihan
                    </button>
                </form>

                <form method="POST" action="{{ route('weddings.photos.store', $wedding) }}"
                      enctype="multipart/form-data" class="mt-5 space-y-3 border-t border-stone-100 pt-5">
                    @csrf

                    <div>
                        <label for="photos" class="{{ $labelClass }}">Unggah foto (JPG, PNG, WebP)</label>
                        <input id="photos" name="photos[]" type="file" multiple required
                               accept="image/jpeg,image/png,image/webp"
                               class="mt-1 block w-full text-sm text-stone-600 file:mr-3 file:rounded-md file:border-0 file:bg-stone-100 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-stone-700 hover:file:bg-stone-200">
                        <p class="mt-1 text-xs text-stone-500">
                            Maksimal {{ $limits->maxFiles() }} foto sekali unggah, {{ $limits->fileMb() }} MB per foto.
                            Foto masuk album &ldquo;Prewedding&rdquo; pada galeri undangan ini.
                        </p>
                        @error('photos')
                            <p class="mt-1 text-xs text-rose-800">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="rounded-md bg-rose-800 px-4 py-2 text-sm font-medium text-white hover:bg-rose-900">
                        Unggah foto
                    </button>
                </form>

                @if ($photos->isNotEmpty())
                    <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3">
                        @foreach ($photos as $photo)
                            @php $isCover = $photo->getKey() === $coverId; @endphp
                            <figure class="overflow-hidden rounded-md border {{ $isCover ? 'border-rose-700 ring-1 ring-rose-700' : 'border-stone-200' }}">
                                <img src="{{ $photoThumbUrls[$photo->getKey()] }}" alt="Foto pre-wedding"
                                     width="400" height="400" loading="lazy" decoding="async"
                                     class="h-28 w-full object-cover">
                                <figcaption class="space-y-1 p-2">
                                    @if ($isCover)
                                        <p class="text-xs font-medium text-rose-800">Sampul undangan</p>
                                    @else
                                        <form method="POST" action="{{ route('weddings.photos.cover', [$wedding, $photo]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-xs text-stone-600 hover:underline">Jadikan sampul</button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route('weddings.gallery.photos.destroy', [$wedding, $photo]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-stone-500 hover:text-rose-800">Hapus</button>
                                    </form>
                                </figcaption>
                            </figure>
                        @endforeach
                    </div>

                    @if ($photoDisplay === \App\Models\Wedding::PHOTO_DISPLAY_MONOGRAM)
                        <p class="mt-3 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs leading-relaxed text-amber-900">
                            Foto tersimpan, tetapi undangan sedang memakai monogram. Pilih &ldquo;Pakai foto pre-wedding&rdquo;
                            lalu simpan untuk menampilkannya.
                        </p>
                    @endif
                @else
                    <p class="mt-4 rounded-md bg-stone-50 px-3 py-2 text-xs leading-relaxed text-stone-600">
                        Belum ada foto. Undangan memakai monogram inisial sampai Anda mengunggah foto.
                    </p>
                @endif
            </section>
        </div>

        <aside class="space-y-6">
            <section class="rounded-lg border border-stone-200 bg-white p-5">
                <h2 class="font-serif text-lg font-semibold">Publikasi</h2>
                @if ($active)
                    <p class="mt-2 text-sm text-stone-700">
                        Versi {{ $active->version_number }} aktif sampai
                        {{ $active->expires_at?->locale(app()->getLocale())->translatedFormat('d F Y') ?? 'tanpa batas' }}.
                    </p>
                    <p class="mt-2 text-xs text-stone-500">
                        Draf yang disimpan sekarang belum tampil sampai diterbitkan ulang.
                    </p>
                @else
                    <p class="mt-2 text-sm text-stone-600">Belum ada publikasi aktif.</p>
                @endif
                <a href="{{ route('weddings.show', $wedding) }}"
                   class="mt-4 inline-block rounded-md bg-rose-800 px-4 py-2 text-sm font-medium text-white hover:bg-rose-900">
                    Kelola publikasi
                </a>
            </section>

            <section class="rounded-lg border border-stone-200 bg-white p-5">
                <h2 class="font-serif text-lg font-semibold">Masa aktif</h2>
                @if ($entitlement)
                    <p class="mt-2 text-sm text-stone-700">
                        {{ $entitlement->expires_at?->locale(app()->getLocale())->translatedFormat('d F Y') ?? 'Tanpa batas' }}
                    </p>
                @else
                    <p class="mt-2 text-sm text-amber-800">Belum ada entitlement aktif.</p>
                @endif
            </section>
        </aside>
    </div>
@endsection
