@extends('layouts.marketing')

@section('title', 'Katalog Tema Undangan &middot; '.config('app.name'))

@php
    $siteName = config('app.name');

    // A chip always keeps the other filter, so narrowing by package never
    // throws away the category that was already chosen.
    $categoryUrl = static fn (?string $key, ?string $tier): string => route(
        'templates.index',
        array_filter(['kategori' => $key, 'paket' => $tier], static fn (?string $value): bool => $value !== null),
    );
    $tierUrl = static fn (?string $key, ?string $category): string => route(
        'templates.index',
        array_filter(['kategori' => $category, 'paket' => $key], static fn (?string $value): bool => $value !== null),
    );

    $chipOn  = 'inline-flex items-center rounded-full bg-rose-800 px-4 py-1.5 text-xs font-semibold tracking-wide text-white shadow-sm transition-all';
    $chipOff = 'inline-flex items-center rounded-full border border-stone-200 bg-white px-4 py-1.5 text-xs font-medium tracking-wide text-stone-600 shadow-sm transition-all hover:border-rose-300 hover:text-rose-800';
@endphp

@section('meta')
    <meta name="description" content="Katalog tema undangan pernikahan {{ $siteName }}: filter oleh kategori dan paket, buka contoh penuh setiap tema sebelum memilih.">
    <link rel="canonical" href="{{ route('templates.index') }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="Katalog Tema Undangan · {{ $siteName }}">
    <meta property="og:description" content="Filter tema undangan digital berdasarkan kategori dan paket. {{ $templates->total() }}+ tema tersedia.">
    <meta property="og:url" content="{{ route('templates.index') }}">
    <meta property="og:image" content="{{ asset('og-wedly.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale" content="id_ID">

    @include('seo.json-ld', ['data' => $structuredData])
@endsection

@section('content')
    {{-- Page Header --}}
    <section class="border-b border-stone-100 bg-gradient-to-b from-[#FFF9F4] to-white">
        <div class="mx-auto max-w-6xl px-4 py-14 sm:py-16">
            <div class="max-w-3xl reveal">
                <p class="section-eyebrow">Katalog Tema</p>
                <h1 class="mt-3 font-serif text-3xl font-semibold leading-tight text-stone-900 sm:text-4xl">
                    Setiap tema adalah halaman undangan<br class="hidden sm:block"> yang sungguhan
                </h1>
                <p class="mt-4 max-w-2xl text-base leading-relaxed text-stone-600">
                    Semua contoh dibuka sebagai undangan lengkap dengan data contoh: sampul, hitung mundur, RSVP,
                    ucapan tamu, dan galeri. Tema bertanda paket tertentu baru dapat dipakai setelah pesanan aktif
                    &mdash; harga <strong>sekali bayar</strong>, tanpa biaya tambahan per desain.
                </p>
            </div>

            {{-- Filter Chips --}}
            <div class="mt-10 space-y-5 reveal">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="mr-2 text-xs font-semibold uppercase tracking-[0.22em] text-stone-400">Kategori</span>
                    <a href="{{ $categoryUrl(null, $tier) }}" class="{{ $category === null ? $chipOn : $chipOff }}">
                        Semua
                    </a>
                    @foreach (App\Support\Templates\TemplatePresentation::CATEGORIES as $key => $label)
                        @continue(! isset($categories[$key]))
                        <a href="{{ $categoryUrl($key, $tier) }}" class="{{ $category === $key ? $chipOn : $chipOff }}">
                            {{ $label }}
                            <span class="ml-1.5 opacity-70">{{ $categories[$key] }}</span>
                        </a>
                    @endforeach
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="mr-2 text-xs font-semibold uppercase tracking-[0.22em] text-stone-400">Paket</span>
                    <a href="{{ $tierUrl(null, $category) }}" class="{{ $tier === null ? $chipOn : $chipOff }}">
                        Semua
                    </a>
                    @foreach (App\Support\Templates\TemplatePresentation::TIER_LABELS as $key => $label)
                        @continue(! isset($tiers[$key]))
                        <a href="{{ $tierUrl($key, $category) }}" class="{{ $tier === $key ? $chipOn : $chipOff }}">
                            {{ $label }}
                            <span class="ml-1.5 opacity-70">{{ $tiers[$key] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- Catalog Grid --}}
    <section class="mx-auto max-w-6xl px-4 py-10 sm:py-14">
        <div data-catalog-results>
            @include('templates.partials.cards')
        </div>
    </section>

    {{-- Bottom CTA --}}
    <section class="border-t border-stone-100 bg-white">
        <div class="mx-auto max-w-6xl px-4 py-14 sm:py-16">
            <div class="grid gap-6 sm:grid-cols-2 lg:gap-10 reveal-stagger">
                <div class="rounded-2xl border border-stone-100 bg-[#FFF9F4] p-7">
                    <div class="flex h-11 w-11 items-center justify-center rounded-full bg-rose-100 text-rose-800">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                    </div>
                    <h2 class="mt-4 font-serif text-xl font-semibold text-stone-900">Sudah punya tema favorit?</h2>
                    <p class="mt-2 text-sm leading-relaxed text-stone-600">
                        Tema dipilih saat membuat undangan dan dapat diganti selama belum terbit.
                        Versi yang sudah dibagikan tidak berubah sampai Anda menerbitkan ulang.
                    </p>
                    <a href="{{ auth()->check() ? route('weddings.create') : route('register') }}"
                       class="btn-primary mt-5 w-full sm:w-auto">
                        {{ auth()->check() ? 'Mulai buat undangan' : 'Buat akun gratis' }}
                    </a>
                </div>

                <div class="rounded-2xl border border-stone-100 bg-white p-7">
                    <div class="flex h-11 w-11 items-center justify-center rounded-full bg-stone-100 text-stone-700">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        </svg>
                    </div>
                    <h2 class="mt-4 font-serif text-xl font-semibold text-stone-900">Butuh desain yang lebih personal?</h2>
                    <p class="mt-2 text-sm leading-relaxed text-stone-600">
                        Ceritakan tema dan warna yang Anda bayangkan. Kami tinjau ketersediaan desain tambahan
                        sebelum Anda memesan paket.
                    </p>
                    <a href="{{ route('home') }}#kontak"
                       class="btn-secondary mt-5 w-full sm:w-auto">
                        Konsultasikan tema
                    </a>
                </div>
            </div>
        </div>
    </section>
@endsection
