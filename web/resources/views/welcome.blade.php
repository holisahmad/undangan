@extends('layouts.marketing')

@section('title', config('app.name').' — Undangan Pernikahan Digital Elegan & Wedding Organizer')

@php
    $siteName = config('app.name');
    $homeUrl = route('home');
    $contact = config('site.contact');
    $whatsapp = App\Support\Contact\PhoneNumber::whatsappLink((string) ($contact['whatsapp'] ?? ''));
    $phoneHref = App\Support\Contact\PhoneNumber::e164((string) ($contact['phone'] ?? ''));
    $phone = App\Support\Contact\PhoneNumber::display((string) ($contact['phone'] ?? ''));
    $addressLines = App\Support\Contact\BusinessAddress::lines((array) config('site.address', []));
    $mapsUrl = (string) ($contact['maps_url'] ?? '');

    $promo = (array) config('site.promo', []);
    $promoLabel = trim((string) ($promo['label'] ?? ''));
    $promoNote = trim((string) ($promo['note'] ?? ''));
    $promoEndsAt = trim((string) ($promo['ends_at'] ?? ''));
    $promoDeadline = $promoEndsAt === '' ? null : \Illuminate\Support\Carbon::parse($promoEndsAt);

    $services = App\Support\Marketing\WeddingServices::all();
    $servicesPhone = App\Support\Contact\PhoneNumber::whatsappLink((string) config('site.services_phone', ''));
@endphp

@section('meta')
    <meta name="description" content="{{ config('site.description') }}">
    <link rel="canonical" href="{{ $homeUrl }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $siteName }} — Undangan Pernikahan Digital Elegan & Modern">
    <meta property="og:description" content="{{ config('site.description') }}">
    <meta property="og:url" content="{{ $homeUrl }}">
    <meta property="og:image" content="{{ asset('og-wedly.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ $siteName }} — Undangan Pernikahan Digital">
    <meta property="og:locale" content="id_ID">
    <meta name="twitter:title" content="{{ $siteName }} — Undangan Pernikahan Digital Elegan">
    <meta name="twitter:description" content="{{ config('site.description') }}">

    @include('seo.json-ld', ['data' => $structuredData])
@endsection

@section('content')
    {{-- 1. Promotional Flash Banner --}}
    @if ($promoLabel !== '' && $promoDeadline !== null && $promoDeadline->isFuture())
        <section class="bg-gradient-to-r from-rose-950 via-rose-900 to-rose-950 text-white shadow-inner">
            <div class="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-2.5 text-center sm:flex-row sm:items-center sm:justify-center sm:gap-4 sm:text-left text-xs">
                <div class="flex items-center justify-center gap-2">
                    <span class="flex h-2 w-2 rounded-full bg-rose-400 animate-pulse"></span>
                    <span class="font-semibold uppercase tracking-wider text-rose-200">Penawaran Spesial:</span>
                    <span class="font-medium text-white">{{ $promoLabel }}</span>
                </div>
                @if ($promoNote !== '')
                    <span class="hidden md:inline text-rose-300">&bull;</span>
                    <span class="text-rose-200 hidden md:inline">{{ $promoNote }}</span>
                @endif
                <span class="font-mono text-rose-100" data-countdown-to="{{ $promoDeadline->toAtomString() }}" data-countdown-expired="Promo ini telah berakhir">
                    <span data-countdown-label>Berlaku hingga {{ $promoDeadline->copy()->locale('id')->translatedFormat('d F Y') }}</span>
                </span>
                <a href="#kontak" class="inline-flex items-center gap-1 rounded bg-rose-800/80 px-2 py-0.5 font-semibold text-white underline underline-offset-2 hover:bg-rose-700">
                    Klaim Promo &rarr;
                </a>
            </div>
        </section>
    @endif

    {{-- 2. Hero Section: Eye-Catching & High-Converting --}}
    <section class="relative overflow-hidden bg-gradient-to-b from-[#FFF9F4] via-[#FAF5EE] to-[#FCFBF8] pt-10 pb-10 sm:pt-20 sm:pb-20">
        {{-- Background Soft Blobs --}}
        <div class="pointer-events-none absolute -top-32 -left-32 h-96 w-96 rounded-full bg-rose-200/40 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute top-40 right-0 h-96 w-96 rounded-full bg-amber-200/30 blur-3xl" aria-hidden="true"></div>

        <div class="mx-auto max-w-6xl px-4">
            <div class="grid items-center gap-12 lg:grid-cols-12 lg:gap-8">
                {{-- Left Text & CTA Column --}}
                <div class="reveal lg:col-span-7">
                    <div class="inline-flex items-center gap-2 rounded-full border border-rose-200/80 bg-white/80 px-3.5 py-1.5 shadow-sm backdrop-blur">
                        <span class="flex h-2 w-2 rounded-full bg-rose-700 animate-ping"></span>
                        <span class="text-xs font-semibold uppercase tracking-[0.2em] text-rose-900">Platform Undangan &amp; Wedding Organizer</span>
                    </div>

                    <h1 class="mt-6 font-serif text-4xl font-bold tracking-tight text-stone-900 sm:text-5xl lg:text-6xl sm:leading-[1.15]">
                        Undangan Digital Mewah, <br class="hidden sm:inline">
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-rose-900 via-rose-700 to-amber-700">
                            Penuh Kebahagiaan
                        </span>
                    </h1>

                    <p class="mt-5 text-base sm:text-lg leading-relaxed text-stone-600 max-w-xl">
                        {{ config('site.description') }} Solusi lengkap dengan tautan nama tamu otomatis, RSVP real-time, amplop digital, dan koordinasi vendor pelaminan terpercaya.
                    </p>

                    {{-- Actions --}}
                    <div class="mt-8 flex flex-col gap-3.5 sm:flex-row sm:items-center">
                        <a href="{{ auth()->check() ? route('weddings.create') : route('register') }}"
                           class="btn-primary text-base py-3.5 px-7">
                            <span>Mulai Buat Undangan</span>
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </a>
                        <a href="{{ route('templates.index') }}"
                           class="btn-secondary text-base py-3.5 px-6">
                            @if ($templateCount > 0)
                                Jelajahi {{ $templateCount }} Tema Mewah
                            @else
                                Lihat Katalog Tema
                            @endif
                        </a>
                    </div>

                    {{-- Value Props Checkmarks --}}
                    <div class="mt-8 flex flex-wrap items-center gap-x-6 gap-y-2 text-xs sm:text-sm font-medium text-stone-600">
                        <div class="flex items-center gap-1.5">
                            <span class="flex h-4 w-4 items-center justify-center rounded-full bg-emerald-100 text-emerald-800 text-[10px]">&check;</span>
                            <span>Tamu buka tanpa instal aplikasi</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="flex h-4 w-4 items-center justify-center rounded-full bg-emerald-100 text-emerald-800 text-[10px]">&check;</span>
                            <span>Buku tamu &amp; RSVP otomatis</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="flex h-4 w-4 items-center justify-center rounded-full bg-emerald-100 text-emerald-800 text-[10px]">&check;</span>
                            <span>Privasi tamu terjaga aman</span>
                        </div>
                    </div>

                    <p class="mt-6 font-serif text-sm italic tracking-wide text-rose-900/80">
                        &ldquo;{{ config('site.tagline') }}&rdquo;
                    </p>
                </div>

                {{-- Right Interactive Phone Mockup Column --}}
                <div class="reveal lg:col-span-5 flex justify-center lg:justify-end hidden sm:flex">
                    <div class="relative w-full max-w-[280px] sm:max-w-[340px]" data-mockup-tilt>
                        {{-- Floating Badges around Phone --}}
                        <div class="absolute -top-4 -left-6 z-20 hidden sm:flex items-center gap-2.5 rounded-xl border border-white/60 bg-white/95 p-3 shadow-xl backdrop-blur-md animate-float">
                            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-rose-100 text-rose-800">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-stone-900">247 RSVP Masuk</p>
                                <p class="text-[10px] text-stone-500">Konfirmasi Hadir 94%</p>
                            </div>
                        </div>

                        <div class="absolute -bottom-6 -right-6 z-20 hidden sm:flex items-center gap-2.5 rounded-xl border border-white/60 bg-white/95 p-3 shadow-xl backdrop-blur-md">
                            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-amber-100 text-amber-700 font-bold">
                                ★
                            </div>
                            <div>
                                <p class="text-xs font-bold text-stone-900">Rating 4.9/5</p>
                                <p class="text-[10px] text-stone-500">Dari 500+ Pasangan</p>
                            </div>
                        </div>

                        {{-- Smartphone Frame Mockup --}}
                        <div class="relative overflow-hidden rounded-[40px] border-[10px] border-stone-800 bg-white shadow-2xl ring-1 ring-black/10">
                            {{-- Phone Notch / Speaker --}}
                            <div class="absolute top-0 left-1/2 z-30 h-4 w-28 -translate-x-1/2 rounded-b-xl bg-stone-800 flex items-center justify-center">
                                <div class="h-1 w-8 rounded-full bg-stone-700"></div>
                            </div>

                            {{-- Phone Screen Content: Romantic Card Preview --}}
                            <div class="relative flex flex-col items-center bg-[#FAF6F0] p-5 pt-10 pb-6 text-center min-h-[420px] sm:min-h-[500px]">
                                <div class="h-1 w-16 bg-gradient-to-r from-transparent via-rose-300 to-transparent mb-4"></div>
                                <span class="text-[10px] uppercase tracking-[0.3em] text-rose-800 font-semibold">The Wedding Of</span>
                                
                                <h2 class="mt-4 font-serif text-3xl font-normal text-stone-900 leading-tight">
                                    Aditya <br>&amp; Saraswati
                                </h2>

                                <div class="my-4 flex items-center gap-2 text-rose-700">
                                    <span class="h-px w-8 bg-rose-200"></span>
                                    <span class="text-xs">&diams;</span>
                                    <span class="h-px w-8 bg-rose-200"></span>
                                </div>

                                <p class="text-xs font-medium text-stone-600">Minggu, 18 Oktober 2026</p>
                                <p class="text-[11px] text-stone-500 mt-0.5">Grand Ballroom, Jakarta</p>

                                {{-- Guest Personalization simulation --}}
                                <div class="mt-6 w-full rounded-xl border border-stone-200/60 bg-white/90 p-4 shadow-sm text-left">
                                    <p class="text-[10px] uppercase tracking-wider text-stone-400">Kepada Yth:</p>
                                    <p class="mt-1 text-xs font-bold text-stone-800">Bpk. Rizky &amp; Partner</p>
                                    <p class="mt-1 text-[10px] text-emerald-700 font-medium">&bull; Tautan Eksklusif &amp; Reservasi Tempat</p>
                                </div>

                                {{-- Fake Open Invitation Button --}}
                                <div class="mt-6 w-full rounded-lg bg-rose-800 py-2.5 text-xs font-semibold text-white shadow-md">
                                    Buka Undangan
                                </div>

                                <p class="mt-4 text-[9px] text-stone-400 tracking-wider">Wedly Wedding Invitation System</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 3. Live Stats Bar --}}
    @if ($stats !== [])
        <section class="border-y border-stone-200 bg-white py-10 shadow-sm">
            <div class="mx-auto max-w-6xl px-4">
                <div class="grid grid-cols-2 gap-6 md:grid-cols-4 lg:divide-x lg:divide-stone-100">
                    @foreach ($stats as $stat)
                        <div class="text-center px-4">
                            <p class="font-serif text-3xl sm:text-4xl font-bold text-rose-900">{{ $stat['value'] }}</p>
                            <p class="mt-1.5 text-xs font-medium leading-relaxed text-stone-600">{{ $stat['label'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- 4. Features Section: Yang Anda Dapatkan --}}
    <section class="mx-auto max-w-6xl px-4 pt-8 pb-6 sm:pt-16 sm:pb-12">
        <div class="text-center max-w-2xl mx-auto reveal">
            <p class="section-eyebrow">Fitur Unggulan</p>
            <h2 class="mt-2 font-serif text-3xl font-bold sm:text-4xl text-stone-900">
                Yang Anda Dapatkan di Setiap Undangan
            </h2>
            <p class="mt-3 text-sm sm:text-base text-stone-600 leading-relaxed">
                Bukan daftar impian semata. Semua fitur di bawah ini siap pakai untuk memberikan pengalaman tak terlupakan bagi tamu dan ketenangan untuk mempelai.
            </p>
        </div>

        <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3 reveal-stagger">
            @foreach (config('site.features', []) as $feature)
                <div class="card-modern p-6 flex flex-col">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-rose-50 text-rose-800">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M20 6L9 17l-5-5" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-base font-bold text-stone-900">{{ $feature['title'] }}</h3>
                    <p class="mt-2 flex-1 text-sm leading-relaxed text-stone-600">{{ $feature['text'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- 5. Template Showcase: Contoh Undangan Nyata --}}
    <section id="contoh" class="border-t border-stone-200 bg-[#FFFDF9] pt-10 pb-14 sm:pt-14 sm:pb-20">
        <div class="mx-auto max-w-6xl px-4">
            <div class="flex flex-wrap items-end justify-between gap-4 reveal">
                <div>
                    <p class="section-eyebrow">Katalog Pilihan</p>
                    <h2 class="mt-2 font-serif text-3xl font-bold text-stone-900">Tema Undangan Paling Diminati</h2>
                    <p class="mt-2 max-w-2xl text-sm sm:text-base text-stone-600">
                        Setiap template dapat dibuka sebagai undangan penuh dengan data percontohan lengkap.
                    </p>
                </div>
                @if ($templateCount > count($templates))
                    <a href="{{ route('templates.index') }}" class="btn-secondary text-xs font-semibold py-2.5">
                        Lihat semua {{ $templateCount }} tema &rarr;
                    </a>
                @endif
            </div>

            @if ($templates->isEmpty())
                <p class="mt-8 text-sm text-stone-500">Belum ada template yang terbit.</p>
            @else
                <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3 reveal-stagger">
                    @foreach ($templates as $template)
                        @include('invitations.partials.template-card', [
                            'template' => $template,
                            'version' => $template->versions->first(),
                        ])
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- 6. How It Works: Tiga Langkah Mudah --}}
    <section class="border-t border-stone-200 bg-white py-12 sm:py-18">
        <div class="mx-auto max-w-6xl px-4">
            <div class="text-center max-w-2xl mx-auto reveal">
                <p class="section-eyebrow">Alur Kerja Praktis</p>
                <h2 class="mt-2 font-serif text-3xl font-bold text-stone-900">Hanya 3 Langkah Mudah</h2>
                <p class="mt-3 text-sm text-stone-600">Undangan selesai dalam hitungan menit, langsung siap dibagikan ke keluarga dan kerabat.</p>
            </div>

            <div class="mt-14 grid gap-8 md:grid-cols-3 relative reveal-stagger">
                {{-- Step 1 --}}
                <div class="relative flex flex-col items-center text-center p-6 rounded-2xl bg-stone-50 border border-stone-100">
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-rose-800 text-white font-serif font-bold text-lg shadow-md">
                        1
                    </span>
                    <h3 class="mt-5 text-base font-bold text-stone-900">Pilih Template &amp; Isi Data</h3>
                    <p class="mt-2 text-sm leading-relaxed text-stone-600">
                        Pilih desain yang mencerminkan kisah cinta Anda, lalu lengkapi detail mempelai, jadwal akad, resepsi, serta kisah perjalanan cinta.
                    </p>
                </div>

                {{-- Step 2 --}}
                <div class="relative flex flex-col items-center text-center p-6 rounded-2xl bg-stone-50 border border-stone-100">
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-rose-800 text-white font-serif font-bold text-lg shadow-md">
                        2
                    </span>
                    <h3 class="mt-5 text-base font-bold text-stone-900">Impor Tamu &amp; Personalisasi</h3>
                    <p class="mt-2 text-sm leading-relaxed text-stone-600">
                        Unggah daftar nama tamu via Excel/CSV. Sistem langsung membuatkan tautan personal otomatis untuk setiap tamu istimewa Anda.
                    </p>
                </div>

                {{-- Step 3 --}}
                <div class="relative flex flex-col items-center text-center p-6 rounded-2xl bg-stone-50 border border-stone-100">
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-rose-800 text-white font-serif font-bold text-lg shadow-md">
                        3
                    </span>
                    <h3 class="mt-5 text-base font-bold text-stone-900">Bagikan &amp; Pantau RSVP</h3>
                    <p class="mt-2 text-sm leading-relaxed text-stone-600">
                        Kirim tautan langsung lewat WhatsApp, pantau konfirmasi kehadiran real-time, dan rekap ucapan doa di dashboard Anda.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- 7. Wedding Organizer Services: Pelaminan & Vendor Terpadu --}}
    @if ($services !== [])
        <section id="layanan" class="border-t border-stone-200 bg-[#FAF7F2] py-16 sm:py-24">
            <div class="mx-auto max-w-6xl px-4">
                <div class="flex flex-wrap items-end justify-between gap-6 reveal">
                    <div class="max-w-2xl">
                        <p class="section-eyebrow">Pelayanan Wedding Organizer</p>
                        <h2 class="mt-2 font-serif text-3xl font-bold text-stone-900 sm:text-4xl">
                            Rias Pengantin, Dekorasi, Hingga Dokumentasi
                        </h2>
                        <p class="mt-3 text-sm sm:text-base leading-relaxed text-stone-600">
                            {{ $siteName }} menyiapkan undangan digitalnya, sekaligus mengoordinasikan layanan pernikahan bersama vendor mitra pilihan. Satu pintu komunikasi untuk pesta pernikahan yang selaras dan terorganisir rapi.
                        </p>
                    </div>
                    @if ($servicesPhone !== null)
                        <a href="{{ $servicesPhone }}" target="_blank" rel="noopener noreferrer"
                           class="btn-secondary py-3 px-5 text-sm font-semibold">
                            Konsultasi Wedding Organizer via WhatsApp
                        </a>
                    @endif
                </div>

                <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3 reveal-stagger">
                    @foreach ($services as $service)
                        <article class="card-modern p-6 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-rose-800 bg-rose-50 px-2.5 py-1 rounded-full">
                                        {{ $service['provider'] }}
                                    </span>
                                </div>
                                <h3 class="mt-4 font-serif text-xl font-bold text-stone-900">{{ $service['name'] }}</h3>
                                <p class="mt-2 text-sm leading-relaxed text-stone-600">{{ $service['text'] }}</p>
                            </div>
                            <div class="mt-6 pt-4 border-t border-stone-100">
                                <a href="{{ route('home', ['layanan' => $service['slug']]) }}#kontak"
                                   class="inline-flex items-center gap-1.5 text-xs font-bold text-rose-800 hover:text-rose-950">
                                    Tanyakan Layanan Ini &rarr;
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>

                <p class="mt-8 text-xs text-stone-500 text-center">
                    Harga, jadwal, dan ketersediaan layanan dikonfirmasi langsung oleh mitranya. Kami tidak mengambil biaya dari pemesanan tersebut.
                </p>
            </div>
        </section>
    @endif

    {{-- 8. Showcase: Pasangan yang Sudah Memakai --}}
    @if ($showcaseWeddings->isNotEmpty())
        <section id="inspirasi" class="border-t border-stone-200 bg-white py-16 sm:py-24">
            <div class="mx-auto max-w-6xl px-4">
                <div class="flex flex-wrap items-end justify-between gap-4 reveal">
                    <div>
                        <p class="section-eyebrow">Galeri Pasangan</p>
                        <h2 class="mt-2 font-serif text-3xl font-bold text-stone-900">Inspirasi Pasangan Nyata</h2>
                        <p class="mt-2 max-w-2xl text-sm sm:text-base text-stone-600">
                            Dibagikan atas izin mempelai. Halaman terbit yang sama persis seperti yang dinikmati oleh para tamu.
                        </p>
                    </div>
                    <a href="{{ route('showcase.index') }}" class="btn-secondary text-xs font-semibold py-2.5">
                        Lihat Semua Inspirasi &rarr;
                    </a>
                </div>

                <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3 reveal-stagger">
                    @foreach ($showcaseWeddings as $wedding)
                        @php
                            $headline = $wedding->partners
                                ->map(static fn ($partner) => $partner->nickname ?: $partner->display_name)
                                ->filter()
                                ->implode(' & ');
                        @endphp
                        <a href="{{ route('showcase.show', ['slug' => $wedding->slug]) }}"
                           class="card-modern p-6 group block">
                            <span class="text-[10px] font-semibold uppercase tracking-widest text-stone-400">Undangan Pernikahan</span>
                            <p class="mt-2 font-serif text-xl font-bold text-stone-900 group-hover:text-rose-900 transition">
                                {{ $headline !== '' ? $headline : 'Undangan Pernikahan' }}
                            </p>
                            <p class="mt-4 inline-flex items-center gap-1 text-xs font-bold text-rose-800">
                                Buka Undangan &rarr;
                            </p>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- 9. Pricing Section: Paket Sekali Bayar --}}
    <section id="harga" class="border-t border-stone-200 bg-[#FFFDF9] py-16 sm:py-24">
        <div class="mx-auto max-w-6xl px-4">
            <div class="text-center max-w-2xl mx-auto reveal">
                <p class="section-eyebrow">Investasi Jujur</p>
                <h2 class="mt-2 font-serif text-3xl font-bold text-stone-900 sm:text-4xl">Paket &amp; Harga Transparan</h2>
                <p class="mt-3 text-sm sm:text-base text-stone-600">
                    Satu kali bayar untuk masa aktif penuh. Bebas biaya tersembunyi, tanpa langganan berulang, tanpa batasan jumlah tamu.
                </p>
            </div>

            @if ($plans->isEmpty())
                <p class="mt-8 rounded-lg border border-amber-200 bg-amber-50 p-4 text-center text-sm text-amber-900">
                    Daftar harga sedang diperbarui. Hubungi kami untuk penawaran terbaru.
                </p>
            @else
                <div class="mt-12 grid gap-6 lg:grid-cols-3 reveal-stagger">
                    @foreach ($plans as $plan)
                        @php
                            $isPopular = strtolower($plan->name) === 'premium' || $loop->iteration === 2;
                        @endphp
                        <section class="relative flex flex-col rounded-2xl border {{ $isPopular ? 'border-rose-800 ring-2 ring-rose-800 shadow-xl' : 'border-stone-200 shadow-sm' }} bg-white p-7">
                            @if ($isPopular)
                                <span class="absolute -top-3.5 left-1/2 -translate-x-1/2 rounded-full bg-rose-800 px-4 py-1 text-[11px] font-bold uppercase tracking-wider text-white shadow-md">
                                    Paling Diminati
                                </span>
                            @endif

                            <div class="border-b border-stone-100 pb-6">
                                <h3 class="font-serif text-xl font-bold text-stone-900">{{ $plan->name }}</h3>
                                <p class="mt-3 font-serif text-4xl font-extrabold text-stone-900">
                                    Rp{{ number_format($plan->price_amount, 0, ',', '.') }}
                                </p>
                                <p class="mt-1 text-xs text-stone-500">Sekali bayar &bull; Masa aktif {{ $plan->duration_months }} bulan</p>
                            </div>

                            <ul class="my-6 flex-1 space-y-3.5 text-sm text-stone-700">
                                @foreach ($plan->features as $feature)
                                    <li class="flex items-start justify-between gap-3 text-xs sm:text-sm">
                                        <span class="text-stone-600 flex items-center gap-1.5">
                                            <span class="text-emerald-700 font-bold">&check;</span>
                                            {{ \App\Support\Billing\PlanFeatureLabel::name($feature->feature_key) }}
                                        </span>
                                        <span class="font-semibold text-stone-900 text-right">{{ \App\Support\Billing\PlanFeatureLabel::value($feature) }}</span>
                                    </li>
                                @endforeach
                            </ul>

                            <a href="{{ auth()->check() ? route('weddings.index') : route('register') }}"
                               class="{{ $isPopular ? 'btn-primary' : 'btn-secondary' }} w-full text-center py-3 text-sm">
                                {{ auth()->check() ? 'Pilih Paket Ini' : 'Daftar Sekarang' }}
                            </a>
                        </section>
                    @endforeach
                </div>

                <div class="mt-8 text-center text-xs text-stone-500 max-w-xl mx-auto space-y-1">
                    <p>Paket dipilih saat penyelesaian pesanan di dashboard. Keamanan transaksi terjamin via transfer bank resmi.</p>
                </div>
            @endif
        </div>
    </section>

    {{-- 10. Testimonials: Kata Pasangan Bahagia --}}
    @if ($testimonials->isNotEmpty())
        <section id="testimoni" class="border-t border-stone-200 bg-white py-16 sm:py-24">
            <div class="mx-auto max-w-6xl px-4">
                <div class="text-center max-w-2xl mx-auto reveal">
                    <p class="section-eyebrow">Kepuasan Pelanggan</p>
                    <h2 class="mt-2 font-serif text-3xl font-bold text-stone-900">Cerita Kebahagiaan Mereka</h2>
                    <p class="mt-3 text-sm text-stone-600">Ulasan tulus dari para pengantin yang telah mempercayakan hari bahagia mereka pada Wedly.</p>
                </div>

                <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3 reveal-stagger">
                    @foreach ($testimonials as $testimonial)
                        <figure class="card-modern p-6 flex flex-col justify-between">
                            <div>
                                <div class="text-amber-500 text-base" aria-label="Penilaian {{ $testimonial->rating }} dari 5">
                                    @for ($star = 1; $star <= 5; $star++)
                                        <span aria-hidden="true">{{ $star <= $testimonial->rating ? '★' : '☆' }}</span>
                                    @endfor
                                </div>
                                <blockquote class="mt-4 text-sm font-medium leading-relaxed text-stone-700 italic">
                                    &ldquo;{{ $testimonial->quote }}&rdquo;
                                </blockquote>
                            </div>
                            <figcaption class="mt-6 pt-4 border-t border-stone-100 flex items-center justify-between text-xs text-stone-500">
                                <span class="font-bold text-stone-800">
                                    {{ \Illuminate\Support\Str::before((string) $testimonial->user?->name, ' ') ?: 'Pelanggan' }}
                                </span>
                                @if ($testimonial->wedding)
                                    <span class="truncate max-w-[150px]">{{ $testimonial->wedding->internal_title }}</span>
                                @endif
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- 11. FAQ: Tanya Jawab --}}
    <section id="faq" class="border-t border-stone-200 bg-[#FFFDF9] py-16 sm:py-24">
        <div class="mx-auto max-w-3xl px-4">
            <div class="text-center reveal">
                <p class="section-eyebrow">Informasi Penting</p>
                <h2 class="mt-2 font-serif text-3xl font-bold text-stone-900">Pertanyaan umum</h2>
                <p class="mt-3 text-sm text-stone-600">Semua yang perlu Anda ketahui mengenai platform dan operasional kami.</p>
            </div>

            <div class="mt-10 divide-y divide-stone-200 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm reveal">
                @foreach ($faqs as $faq)
                    <details class="group py-4 first:pt-0 last:pb-0">
                        <summary class="flex cursor-pointer items-center justify-between gap-4 text-sm sm:text-base font-semibold text-stone-900 marker:content-none select-none">
                            <span>{{ $faq['question'] }}</span>
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-stone-100 text-stone-500 transition group-open:rotate-45 group-open:bg-rose-100 group-open:text-rose-800 font-normal">
                                +
                            </span>
                        </summary>
                        <p class="mt-3 text-sm leading-relaxed text-stone-600 pl-1">{{ $faq['answer'] }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- 12. Final High-Impact CTA Banner --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-rose-950 via-rose-900 to-rose-950 text-white py-16 sm:py-20">
        <div class="pointer-events-none absolute -right-20 -bottom-20 h-80 w-80 rounded-full bg-rose-700/20 blur-3xl" aria-hidden="true"></div>
        <div class="mx-auto max-w-4xl px-4 text-center relative z-10 reveal">
            <p class="text-xs uppercase tracking-[0.3em] font-semibold text-rose-300">Wedly Wedding Organizer</p>
            <h2 class="mt-3 font-serif text-3xl font-bold sm:text-4xl lg:text-5xl leading-tight">
                Hari Istimewa Anda Layak <br class="hidden sm:inline"> Diumumkan dengan Indah.
            </h2>
            <p class="mx-auto mt-4 max-w-xl text-sm sm:text-base text-rose-100 leading-relaxed">
                Susun rancangan undangan pernikahan impian Anda sekarang juga. Terbitkan versi snapshot yang stabil, aman, dan bagikan tautan eksklusif ke seluruh tamu tercinta.
            </p>
            <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ auth()->check() ? route('weddings.create') : route('register') }}"
                   class="w-full sm:w-auto rounded-xl bg-white px-8 py-3.5 text-sm font-bold text-rose-950 shadow-xl transition hover:bg-rose-50 hover:scale-105">
                    Buat Undangan Saya Gratis
                </a>
                <a href="#contoh"
                   class="w-full sm:w-auto rounded-xl border border-rose-300/60 px-7 py-3.5 text-sm font-semibold text-white transition hover:bg-white/10">
                    Buka Contoh Undangan
                </a>
            </div>
        </div>
    </section>

    {{-- 13. Contact & Lead Form: Konsultasi & Layanan --}}
    <section id="kontak" class="mx-auto max-w-6xl px-4 py-16 sm:py-24">
        <div class="grid gap-8 lg:grid-cols-2">
            {{-- Contact Information Card --}}
            <div class="card-modern p-6 sm:p-8 flex flex-col justify-between">
                <div>
                    <p class="section-eyebrow">Pusat Konsultasi</p>
                    <h2 class="mt-2 font-serif text-2xl sm:text-3xl font-bold text-stone-900">Masih Ada Pertanyaan?</h2>
                    <p class="mt-3 text-sm text-stone-600 leading-relaxed">
                        Tim wedding consultant kami siap mendengarkan rencana hari bahagia Anda. Kami merespon pesan dengan hangat dan cepat.
                    </p>

                    <div class="mt-8 space-y-4 text-sm text-stone-700">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50 text-rose-800 shrink-0 font-bold">@</span>
                            <div>
                                <p class="text-xs text-stone-500">Email Resmi</p>
                                <a href="mailto:{{ $contact['email'] }}" class="font-semibold text-stone-900 hover:text-rose-800">{{ $contact['email'] }}</a>
                            </div>
                        </div>

                        @if ($phoneHref !== null)
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50 text-rose-800 shrink-0 font-bold">&phone;</span>
                                <div>
                                    <p class="text-xs text-stone-500">Telepon Studio</p>
                                    <a href="tel:{{ $phoneHref }}" class="font-semibold text-stone-900 hover:text-rose-800">
                                        {{ $phone }}
                                    </a>
                                </div>
                            </div>
                        @endif

                        @if ($whatsapp !== null)
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 shrink-0 font-bold">&phone;</span>
                                <div>
                                    <p class="text-xs text-stone-500">WhatsApp Hot-line</p>
                                    <a href="{{ $whatsapp }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-emerald-800 hover:underline">
                                        Chat WhatsApp: {{ $phone }}
                                    </a>
                                </div>
                            </div>
                        @endif

                        <div class="flex items-start gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-800 shrink-0 font-bold">&#9873;</span>
                            <div>
                                <p class="text-xs text-stone-500">Studio &amp; Kantor</p>
                                <p class="font-medium text-stone-800 leading-relaxed">
                                    {{ config('site.operator') }} <br>
                                    @foreach ($addressLines as $line)
                                        {{ $line }}@if (! $loop->last), @endif
                                    @endforeach
                                    @if ($mapsUrl !== '')
                                        <br><a class="text-xs text-rose-800 underline hover:text-rose-950 font-semibold" href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer">Buka di Google Maps &rarr;</a>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-stone-100 flex flex-wrap gap-3">
                    @if ($whatsapp !== null)
                        <a href="{{ $whatsapp }}" target="_blank" rel="noopener noreferrer" class="btn-primary py-2.5 text-xs font-bold">
                            Hubungi via WhatsApp
                        </a>
                    @endif
                    <a href="mailto:{{ $contact['email'] }}" class="btn-secondary py-2.5 text-xs font-semibold">
                        Kirim Email
                    </a>
                </div>
            </div>

            {{-- Lead Capture Form --}}
            <div class="card-modern p-6 sm:p-8">
                <p class="section-eyebrow">Formulir Minat</p>
                <h2 class="mt-2 font-serif text-2xl sm:text-3xl font-bold text-stone-900">Minta Dihubungi</h2>
                <p class="mt-2 text-sm text-stone-600">
                    Tinggalkan kontak Anda, kami akan segera menghubungi untuk konsultasi gratis tanpa komitmen.
                </p>

                @if (session('lead_status'))
                    <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-900" role="status">
                        &check; {{ session('lead_status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900" role="alert">
                        <p class="font-semibold">Mohon periksa data berikut:</p>
                        <ul class="mt-2 list-disc list-inside space-y-1 text-xs">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('leads.store') }}" class="mt-6 space-y-4">
                    @csrf
                    <input type="hidden" name="source" value="landing">
                    <input type="hidden" name="utm_source" value="{{ request('utm_source') }}">
                    <input type="hidden" name="utm_medium" value="{{ request('utm_medium') }}">
                    <input type="hidden" name="utm_campaign" value="{{ request('utm_campaign') }}">

                    {{-- Honeypot bot protection --}}
                    <div class="hidden" aria-hidden="true">
                        <label for="website">Website</label>
                        <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
                    </div>

                    <div>
                        <label for="lead-name" class="block text-xs font-bold uppercase tracking-wider text-stone-700">Nama Lengkap</label>
                        <input id="lead-name" name="name" type="text" value="{{ old('name') }}" required maxlength="120"
                               placeholder="Contoh: Rian &amp; Maya"
                               class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50/50 px-4 py-2.5 text-sm transition focus:border-rose-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-800/10">
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label for="lead-email" class="block text-xs font-bold uppercase tracking-wider text-stone-700">Email Aktif</label>
                            <input id="lead-email" name="email" type="email" value="{{ old('email') }}" required maxlength="190"
                                   placeholder="nama@email.com"
                                   class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50/50 px-4 py-2.5 text-sm transition focus:border-rose-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-800/10">
                        </div>
                        <div>
                            <label for="lead-whatsapp" class="block text-xs font-bold uppercase tracking-wider text-stone-700">Nomor WhatsApp</label>
                            <input id="lead-whatsapp" name="whatsapp" type="tel" value="{{ old('whatsapp') }}" maxlength="30"
                                   placeholder="0812xxxx"
                                   class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50/50 px-4 py-2.5 text-sm transition focus:border-rose-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-800/10">
                        </div>
                    </div>

                    <div>
                        <label for="lead-service" class="block text-xs font-bold uppercase tracking-wider text-stone-700">Kebutuhan Utama</label>
                        <select id="lead-service" name="service_interest"
                                class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50/50 px-4 py-2.5 text-sm transition focus:border-rose-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-800/10">
                            <option value="">Undangan Digital Website Saja</option>
                            @foreach ($services as $service)
                                <option value="{{ $service['slug'] }}"
                                    @selected((string) old('service_interest', request('layanan')) === $service['slug'])>
                                    {{ $service['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="lead-message" class="block text-xs font-bold uppercase tracking-wider text-stone-700">Pesan / Tanggal Rencana Pernikahan</label>
                        <textarea id="lead-message" name="message" rows="3" maxlength="500"
                                  placeholder="Ceritakan gambaran rencana acara Anda..."
                                  class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50/50 px-4 py-2.5 text-sm transition focus:border-rose-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-800/10">{{ old('message') }}</textarea>
                    </div>

                    <button type="submit" class="btn-primary w-full py-3.5 text-sm font-bold shadow-lg">
                        Kirim Permintaan Konsultasi
                    </button>
                    <p class="text-[11px] text-stone-400 text-center">
                        Privasi Anda terjamin. Kami tidak pernah membagikan data kepada pihak ketiga tanpa izin.
                    </p>
                </form>
            </div>
        </div>
    </section>
@endsection
