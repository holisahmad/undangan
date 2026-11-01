@extends('layouts.app')

@section('title', $wedding->internal_title)

@section('content')
    @php
        $active = $wedding->activePublication;
        $latest = $wedding->latestPublication;
    @endphp

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="{{ route('weddings.index') }}" class="text-sm text-stone-500 hover:underline">&larr; Semua undangan</a>
            <h1 class="mt-2 font-serif text-2xl font-semibold">{{ $wedding->internal_title }}</h1>
            <p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-stone-600">
                <span class="rounded-full bg-stone-200 px-2 py-0.5 text-xs font-medium">
                    {{ $wedding->lifecycle_status->label() }}
                </span>
                <span class="text-stone-400">/u/{{ $wedding->slug }}</span>
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('weddings.edit', $wedding) }}"
               class="rounded-md border border-stone-300 bg-white px-4 py-2 text-sm font-medium hover:border-stone-400">
                Edit data
            </a>
            <a href="{{ route('weddings.preview', $wedding) }}"
               class="rounded-md border border-stone-300 bg-white px-4 py-2 text-sm font-medium hover:border-stone-400">
                Pratinjau
            </a>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="rounded-lg border border-stone-200 bg-white p-5">
                <h2 class="font-serif text-lg font-semibold">Kelengkapan data</h2>
                <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-stone-500">Mempelai</dt>
                        <dd class="font-medium">{{ $wedding->partners->count() }} / 2</dd>
                    </div>
                    <div>
                        <dt class="text-stone-500">Acara</dt>
                        <dd class="font-medium">{{ $wedding->events->count() }}</dd>
                    </div>
                </dl>

                @if ($wedding->partners->isNotEmpty())
                    <ul class="mt-4 space-y-1 text-sm text-stone-700">
                        @foreach ($wedding->partners as $partner)
                            <li>
                                <span class="font-medium">{{ ucfirst($partner->partner_type) }}</span>:
                                {{ $partner->display_name }}
                                @if ($partner->nickname)
                                    <span class="text-stone-500">({{ $partner->nickname }})</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($wedding->events->isNotEmpty())
                    <ul class="mt-3 space-y-1 text-sm text-stone-700">
                        @foreach ($wedding->events as $event)
                            <li>
                                <span class="font-medium">{{ $event->title }}</span> &middot;
                                {{ $event->starts_at->copy()->setTimezone($event->timezone)->locale(app()->getLocale())->translatedFormat('l, d F Y · H:i') }}
                                &middot; {{ $event->timezone }}
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="rounded-lg border border-stone-200 bg-white p-5">
                <h2 class="font-serif text-lg font-semibold">Publikasi</h2>

                @if ($active)
                    <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-stone-500">Versi aktif</dt>
                            <dd class="font-medium">{{ $active->version_number }}</dd>
                        </div>
                        <div>
                            <dt class="text-stone-500">Diterbitkan</dt>
                            <dd class="font-medium">{{ $active->published_at?->locale(app()->getLocale())->translatedFormat('d F Y · H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-stone-500">Aktif sampai</dt>
                            <dd class="font-medium">
                                {{ $active->expires_at?->locale(app()->getLocale())->translatedFormat('d F Y · H:i') ?? 'Tanpa batas' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-stone-500">Tautan publik</dt>
                            <dd class="font-medium">
                                <a href="{{ $publicUrl }}" class="text-rose-800 hover:underline" target="_blank" rel="noopener">
                                    {{ $publicUrl }}
                                </a>
                            </dd>
                        </div>
                    </dl>
                @elseif ($latest)
                    <p class="mt-3 text-sm text-stone-600">
                        Tidak ada publikasi aktif. Versi terakhir ({{ $latest->version_number }})
                        {{ $latest->hasExpired() ? 'sudah berakhir' : 'telah dinonaktifkan' }}.
                    </p>
                @else
                    <p class="mt-3 text-sm text-stone-600">Undangan belum pernah diterbitkan.</p>
                @endif
            </section>
        </div>

        <aside class="space-y-6">
            <section class="rounded-lg border border-stone-200 bg-white p-5">
                <h2 class="font-serif text-lg font-semibold">Tamu &amp; RSVP</h2>
                <p class="mt-2 text-sm text-stone-700">
                    {{ $rsvp['responded'] }} dari {{ $rsvp['invited'] }} tamu sudah merespons
                    ({{ $rsvp['response_rate'] }}%).
                </p>

                <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <dt class="text-stone-500">Hadir</dt>
                        <dd class="font-medium">{{ $rsvp['attending'] }} tamu &middot; {{ $rsvp['attending_people'] }} orang</dd>
                    </div>
                    <div>
                        <dt class="text-stone-500">Tidak hadir</dt>
                        <dd class="font-medium">{{ $rsvp['declined'] }} tamu</dd>
                    </div>
                    <div>
                        <dt class="text-stone-500">Masih ragu</dt>
                        <dd class="font-medium">{{ $rsvp['maybe'] }} tamu</dd>
                    </div>
                    <div>
                        <dt class="text-stone-500">Belum merespons</dt>
                        <dd class="font-medium">{{ $rsvp['pending'] }} tamu</dd>
                    </div>
                </dl>

                <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-stone-100"
                     role="img" aria-label="Tingkat respons {{ $rsvp['response_rate'] }}%">
                    <div class="h-2 rounded-full bg-rose-800" @style(['width: '.$rsvp['response_rate'].'%'])></div>
                </div>

                <a href="{{ route('weddings.guests.index', $wedding) }}"
                   class="mt-3 inline-block text-sm text-rose-800 hover:underline">
                    Kelola tamu
                </a>
            </section>

            @if ($active)
                <section class="rounded-lg border border-stone-200 bg-white p-5">
                    <h2 class="font-serif text-lg font-semibold">Bagikan undangan</h2>
                    <p class="mt-2 text-sm text-stone-600">
                        Tautan publik dapat dibuka tanpa akun. Salin teks siap kirim berikut, lalu bagikan lewat WhatsApp
                        atau aplikasi lain.
                    </p>

                    <p class="mt-3 whitespace-pre-line rounded-md border border-stone-200 bg-stone-50 p-3 text-sm text-stone-700">{{ $shareMessage }}</p>

                    <div class="mt-3 flex flex-col gap-2">
                        <a href="https://wa.me/?text={{ rawurlencode($shareMessage) }}"
                           target="_blank" rel="noopener"
                           class="rounded-md bg-rose-800 px-4 py-2 text-center text-sm font-medium text-white hover:bg-rose-900">
                            Bagikan via WhatsApp
                        </a>
                        <button type="button"
                                data-copy-text="{{ $shareMessage }}"
                                class="rounded-md border border-stone-300 bg-white px-4 py-2 text-sm font-medium hover:border-stone-400">
                            Salin teks undangan
                        </button>
                        <button type="button"
                                data-copy-text="{{ $publicUrl }}"
                                class="rounded-md border border-stone-300 bg-white px-4 py-2 text-sm font-medium hover:border-stone-400">
                            Salin tautan
                        </button>
                        <button type="button"
                                data-share-url="{{ $publicUrl }}"
                                data-share-title="{{ $wedding->internal_title }}"
                                hidden
                                class="rounded-md border border-stone-300 bg-white px-4 py-2 text-sm font-medium hover:border-stone-400">
                            Bagikan lewat aplikasi lain&hellip;
                        </button>
                    </div>

                    <p class="mt-3 text-xs text-stone-500">
                        Untuk menyapa tamu satu per satu, buat tautan personal di
                        <a href="{{ route('weddings.guests.index', $wedding) }}" class="text-rose-800 hover:underline">Kelola tamu</a>.
                        Aplikasi tidak melacak status terkirim atau dibaca.
                    </p>
                </section>
            @endif

            @if ($active)
                <section class="rounded-lg border border-stone-200 bg-white p-5">
                    <h2 class="font-serif text-lg font-semibold">Portofolio publik</h2>
                    <p class="mt-2 text-sm text-stone-600">
                        Setujui undangan ini tampil di halaman inspirasi. Halaman yang tampil memakai versi terbit
                        dan tidak menyertakan data tamu. Anda dapat menariknya kapan saja.
                    </p>

                    @if ($wedding->showcase_enabled)
                        <p class="mt-3 text-sm text-emerald-800">
                            Aktif sejak {{ $wedding->showcase_enabled_at?->translatedFormat('d F Y') }}.
                            <a href="{{ route('showcase.show', ['slug' => $wedding->slug]) }}"
                               class="text-rose-800 underline-offset-4 hover:underline" target="_blank" rel="noopener">Lihat halaman portofolio</a>
                        </p>
                        <form method="POST" action="{{ route('weddings.showcase.update', $wedding) }}" class="mt-3"
                              onsubmit="return confirm('Hentikan tampil di halaman inspirasi?')">
                            @csrf
                            <input type="hidden" name="showcase_enabled" value="0">
                            <button type="submit"
                                    class="w-full rounded-md border border-stone-300 bg-white px-4 py-2 text-sm font-medium hover:border-stone-400">
                                Hentikan portofolio
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('weddings.showcase.update', $wedding) }}" class="mt-3">
                            @csrf
                            <input type="hidden" name="showcase_enabled" value="1">
                            <button type="submit"
                                    class="w-full rounded-md border border-stone-300 bg-white px-4 py-2 text-sm font-medium hover:border-stone-400">
                                Tampilkan di inspirasi
                            </button>
                        </form>
                    @endif
                </section>

                <section class="rounded-lg border border-stone-200 bg-white p-5">
                    <h2 class="font-serif text-lg font-semibold">Testimoni</h2>

                    @if ($wedding->testimonial)
                        <p class="mt-2 text-sm text-stone-700">
                            Status:
                            <span class="font-medium">
                                {{ [\App\Models\Testimonial::STATUS_PENDING => 'menunggu peninjauan', \App\Models\Testimonial::STATUS_APPROVED => 'disetujui', \App\Models\Testimonial::STATUS_REJECTED => 'ditolak'][$wedding->testimonial->status] ?? $wedding->testimonial->status }}
                            </span>.
                            Menyunting ulang akan mengirimkannya kembali ke antrean.
                        </p>
                    @else
                        <p class="mt-2 text-sm text-stone-600">
                            Ceritakan pengalaman Anda. Testimoni tayang setelah ditinjau operator.
                        </p>
                    @endif

                    <form method="POST" action="{{ route('weddings.testimonial.store', $wedding) }}" class="mt-3 space-y-3">
                        @csrf
                        <div>
                            <label for="rating" class="block text-sm font-medium">Penilaian</label>
                            <select id="rating" name="rating"
                                    class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-rose-700 focus:outline-none focus:ring-1 focus:ring-rose-700">
                                @foreach (range(5, 1) as $score)
                                    <option value="{{ $score }}" @selected((int) old('rating', $wedding->testimonial?->rating ?? 5) === $score)>
                                        {{ $score }} bintang
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="quote" class="block text-sm font-medium">Testimoni</label>
                            <textarea id="quote" name="quote" rows="3" required minlength="20" maxlength="500"
                                      class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-rose-700 focus:outline-none focus:ring-1 focus:ring-rose-700">{{ old('quote', $wedding->testimonial?->quote) }}</textarea>
                            <p class="mt-1 text-xs text-stone-500">Minimal 20 karakter, maksimal 500.</p>
                        </div>
                        <button type="submit"
                                class="w-full rounded-md bg-rose-800 px-4 py-2 text-sm font-medium text-white hover:bg-rose-900">
                            Kirim testimoni
                        </button>
                    </form>
                </section>
            @endif

            <section class="rounded-lg border border-stone-200 bg-white p-5">
                <h2 class="font-serif text-lg font-semibold">Galeri</h2>
                <p class="mt-2 text-sm text-stone-700">
                    {{ $photoCount }} foto tersimpan{{ $photoLimit !== null ? ' dari kuota '.$photoLimit : '' }}.
                </p>
                <a href="{{ route('weddings.gallery.index', $wedding) }}"
                   class="mt-3 inline-block text-sm text-rose-800 hover:underline">
                    Kelola galeri
                </a>
            </section>

            <section class="rounded-lg border border-stone-200 bg-white p-5">
                <h2 class="font-serif text-lg font-semibold">Masa aktif</h2>
                @if ($entitlement)
                    <p class="mt-2 text-sm text-stone-700">
                        Aktif sampai
                        <span class="font-medium">
                            {{ $entitlement->expires_at?->locale(app()->getLocale())->translatedFormat('d F Y') ?? 'tanpa batas' }}
                        </span>.
                    </p>
                    @can('update', $wedding)
                        <a href="{{ route('weddings.orders.create', $wedding) }}"
                           class="mt-3 inline-block text-sm text-rose-800 hover:underline">
                            Perpanjang masa aktif
                        </a>
                    @endcan
                @else
                    <p class="mt-2 text-sm text-amber-800">
                        Belum ada masa aktif. Undangan tidak dapat diterbitkan sebelum entitlement diberikan.
                    </p>
                    @can('update', $wedding)
                        <a href="{{ route('weddings.orders.create', $wedding) }}"
                           class="mt-3 inline-block rounded-md bg-rose-800 px-4 py-2 text-sm font-medium text-white hover:bg-rose-900">
                            Pilih paket
                        </a>
                    @endcan
                    <p class="mt-3 text-xs text-stone-500">
                        Operator dapat mengaktifkan tanpa pembayaran dengan
                        <code class="rounded bg-stone-100 px-1">php artisan entitlement:grant-trial {{ $wedding->slug }}</code>
                        untuk keperluan dukungan.
                    </p>
                @endif
            </section>

            <section class="rounded-lg border border-stone-200 bg-white p-5">
                <h2 class="font-serif text-lg font-semibold">Terbitkan</h2>
                <p class="mt-2 text-sm text-stone-600">
                    Menerbitkan akan membekukan data saat ini menjadi snapshot baru. Perubahan draf setelahnya
                    baru terlihat setelah diterbitkan ulang.
                </p>

                <form method="POST" action="{{ route('weddings.publish', $wedding) }}" class="mt-4">
                    @csrf
                    <button type="submit"
                            class="w-full rounded-md bg-rose-800 px-4 py-2 text-sm font-medium text-white hover:bg-rose-900">
                        Terbitkan sekarang
                    </button>
                </form>

                @if ($active)
                    <form method="POST" action="{{ route('weddings.unpublish', $wedding) }}" class="mt-3"
                          onsubmit="return confirm('Nonaktifkan tautan publik undangan ini?')">
                        @csrf
                        <button type="submit"
                                class="w-full rounded-md border border-stone-300 bg-white px-4 py-2 text-sm font-medium hover:border-stone-400">
                            Nonaktifkan tautan
                        </button>
                    </form>
                @endif
            </section>
        </aside>
    </div>
@endsection
