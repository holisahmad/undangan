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
                    <div class="min-w-[16rem] flex-1">
                        <label for="internal_title" class="{{ $labelClass }}">Nama internal</label>
                        <input id="internal_title" name="internal_title" type="text" required maxlength="160"
                               value="{{ old('internal_title', $wedding->internal_title) }}" class="{{ $inputClass }}">
                    </div>
                    <button type="submit" class="rounded-md border border-stone-300 bg-white px-4 py-2 text-sm font-medium hover:border-stone-400">
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

                <form method="POST" action="{{ route('weddings.draft.update', $wedding) }}" class="mt-4 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="template_version_id" class="{{ $labelClass }}">Template</label>
                        <select id="template_version_id" name="template_version_id" class="{{ $inputClass }}">
                            @foreach ($templates as $version)
                                <option value="{{ $version->getKey() }}"
                                    @selected(old('template_version_id', $draft?->template_version_id) === $version->getKey())>
                                    {{ $version->template->name }} (versi {{ $version->version }})
                                </option>
                            @endforeach
                        </select>
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
                        @foreach (['show_story' => 'Cerita', 'show_events' => 'Acara', 'show_map' => 'Peta'] as $key => $label)
                            <label class="mt-2 flex items-center gap-2 text-sm">
                                <input type="hidden" name="content[features][{{ $key }}]" value="0">
                                <input type="checkbox" name="content[features][{{ $key }}]" value="1"
                                       @checked((string) old('content.features.' . $key, data_get($content, 'features.' . $key, true)) === '1')
                                       class="rounded border-stone-300 text-rose-800 focus:ring-rose-700">
                                Tampilkan {{ $label }}
                            </label>
                        @endforeach
                    </fieldset>

                    <button type="submit" class="rounded-md bg-rose-800 px-4 py-2 text-sm font-medium text-white hover:bg-rose-900">
                        Simpan draft
                    </button>
                </form>
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
