@extends('layouts.app')

@section('title', $wedding->internal_title)

@section('content')
    @php
        $active = $wedding->activePublication;
        $latest = $wedding->latestPublication;
        $publicUrl = route('invitations.public', ['slug' => $wedding->slug]);
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
                <h2 class="font-serif text-lg font-semibold">Masa aktif</h2>
                @if ($entitlement)
                    <p class="mt-2 text-sm text-stone-700">
                        Aktif sampai
                        <span class="font-medium">
                            {{ $entitlement->expires_at?->locale(app()->getLocale())->translatedFormat('d F Y') ?? 'tanpa batas' }}
                        </span>.
                    </p>
                @else
                    <p class="mt-2 text-sm text-amber-800">
                        Belum ada masa aktif. Undangan tidak dapat diterbitkan sebelum entitlement diberikan.
                    </p>
                    <p class="mt-2 text-xs text-stone-500">
                        Operator dapat menjalankan
                        <code class="rounded bg-stone-100 px-1">php artisan entitlement:grant-trial {{ $wedding->slug }}</code>.
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
