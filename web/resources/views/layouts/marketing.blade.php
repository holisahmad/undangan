@php
    $siteName = config('app.name');
    $tagline = config('site.tagline');
    $contact = config('site.contact');
    $whatsapp = App\Support\Contact\PhoneNumber::whatsappLink((string) ($contact['whatsapp'] ?? ''));
    $phoneHref = App\Support\Contact\PhoneNumber::e164((string) ($contact['phone'] ?? ''));
    $phoneDisplay = App\Support\Contact\PhoneNumber::display((string) ($contact['phone'] ?? ''));
    $addressLines = App\Support\Contact\BusinessAddress::lines((array) config('site.address', []));
    $mapsUrl = (string) ($contact['maps_url'] ?? '');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $siteName.' — Undangan Pernikahan Digital & Wedding Organizer Profesional')</title>

    @hasSection('meta')
        @yield('meta')
    @else
        <meta name="description" content="{{ config('site.description') }}">
        <link rel="canonical" href="{{ url()->current() }}">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ $siteName }}">
        <meta property="og:title" content="{{ $siteName }} — Undangan Pernikahan Digital Elegan">
        <meta property="og:description" content="{{ config('site.description') }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="{{ asset('og-wedly.png') }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:locale" content="id_ID">
    @endif

    <meta name="twitter:card" content="summary_large_image">
    @include('partials.assets')
</head>
<body class="flex min-h-full flex-col bg-[#FCFBF8] text-stone-900 antialiased selection:bg-rose-100 selection:text-rose-900">
    <a href="#konten" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:shadow">
        Lewati ke konten
    </a>

    {{-- Top Sticky Header with Glassmorphism --}}
    <header class="sticky top-0 z-40 glass-header">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:py-3.5">
            {{-- Brand Logo --}}
            <a href="{{ route('home') }}" class="group flex items-center gap-3">
                <div class="relative flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-tr from-rose-900 to-rose-700 p-0.5 shadow-sm transition group-hover:scale-105">
                    <img src="{{ asset('brand/wedly-mark-96.png') }}" alt="" width="34" height="34"
                         class="h-8 w-8 rounded-full bg-white object-contain" decoding="async">
                </div>
                <div class="flex flex-col">
                    <span class="font-serif text-xl font-bold tracking-tight text-stone-900 group-hover:text-rose-900 transition">{{ $siteName }}</span>
                    <span class="text-[9px] uppercase tracking-[0.22em] text-rose-800 font-semibold -mt-1 hidden sm:block">Wedding Organizer</span>
                </div>
            </a>

            {{-- Main Navigation Menu --}}
            <div class="flex items-center gap-2">
                <nav aria-label="Navigasi utama" class="hidden items-center gap-6 text-sm font-medium sm:flex">
                    <a href="{{ route('templates.index') }}" class="text-stone-600 transition hover:text-rose-900">Tema</a>
                    <a href="{{ route('home') }}#contoh" class="text-stone-600 transition hover:text-rose-900">Contoh</a>
                    <a href="{{ route('showcase.index') }}" class="text-stone-600 transition hover:text-rose-900">Inspirasi</a>
                    @if (App\Support\Marketing\WeddingServices::all() !== [])
                        <a href="{{ route('home') }}#layanan" class="text-stone-600 transition hover:text-rose-900">Layanan</a>
                    @endif
                    <a href="{{ route('home') }}#harga" class="text-stone-600 transition hover:text-rose-900">Paket & Harga</a>
                    <a href="{{ route('home') }}#faq" class="text-stone-600 transition hover:text-rose-900">FAQ</a>
                    <a href="{{ route('home') }}#kontak" class="text-stone-600 transition hover:text-rose-900">Kontak</a>

                    <div class="h-4 w-px bg-stone-200"></div>

                    @auth
                        <a href="{{ route('dashboard') }}" class="btn-primary py-2 px-4 text-xs font-semibold">
                            Buka Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="text-stone-700 transition hover:text-rose-900 font-medium">Masuk</a>
                        <a href="{{ route('register') }}" class="btn-primary py-2 px-4 text-xs font-semibold">
                            Buat Undangan
                        </a>
                    @endauth
                </nav>

                {{-- Mobile Menu --}}
                <details class="relative sm:hidden" data-mobile-menu>
                    <summary aria-label="Buka menu"
                             class="flex h-10 w-10 list-none cursor-pointer items-center justify-center rounded-lg border border-stone-200 bg-white text-stone-700 marker:content-none hover:border-stone-400 [&::-webkit-details-marker]:hidden">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" aria-hidden="true">
                            <path d="M3 6h14M3 10h14M3 14h14" />
                        </svg>
                    </summary>

                    <div class="absolute right-0 top-full z-50 mt-2 w-64 rounded-xl border border-stone-200 bg-white/95 p-3 text-sm shadow-xl backdrop-blur-md">
                        <a href="{{ route('templates.index') }}" class="block rounded-lg px-3 py-2 text-stone-700 hover:bg-rose-50 hover:text-rose-900 font-medium">Katalog Tema</a>
                        <a href="{{ route('home') }}#contoh" class="block rounded-lg px-3 py-2 text-stone-700 hover:bg-rose-50 hover:text-rose-900 font-medium">Contoh Undangan</a>
                        <a href="{{ route('showcase.index') }}" class="block rounded-lg px-3 py-2 text-stone-700 hover:bg-rose-50 hover:text-rose-900 font-medium">Inspirasi Pengantin</a>
                        @if (App\Support\Marketing\WeddingServices::all() !== [])
                            <a href="{{ route('home') }}#layanan" class="block rounded-lg px-3 py-2 text-stone-700 hover:bg-rose-50 hover:text-rose-900 font-medium">Layanan Pelaminan</a>
                        @endif
                        <a href="{{ route('home') }}#harga" class="block rounded-lg px-3 py-2 text-stone-700 hover:bg-rose-50 hover:text-rose-900 font-medium">Paket & Harga</a>
                        <a href="{{ route('home') }}#faq" class="block rounded-lg px-3 py-2 text-stone-700 hover:bg-rose-50 hover:text-rose-900 font-medium">FAQ</a>
                        <a href="{{ route('home') }}#kontak" class="block rounded-lg px-3 py-2 text-stone-700 hover:bg-rose-50 hover:text-rose-900 font-medium">Kontak & Konsultasi</a>

                        <div class="my-2 border-t border-stone-100"></div>

                        @auth
                            <a href="{{ route('dashboard') }}" class="block rounded-lg bg-rose-800 py-2.5 text-center text-xs font-semibold text-white">
                                Dashboard Saya
                            </a>
                        @else
                            <div class="grid grid-cols-2 gap-2 pt-1">
                                <a href="{{ route('login') }}" class="rounded-lg border border-stone-200 py-2 text-center text-xs font-medium text-stone-700 hover:bg-stone-50">
                                    Masuk
                                </a>
                                <a href="{{ route('register') }}" class="rounded-lg bg-rose-800 py-2 text-center text-xs font-semibold text-white">
                                    Daftar
                                </a>
                            </div>
                        @endauth
                    </div>
                </details>
            </div>
        </div>
    </header>

    {{-- Main Content --}}
    <main id="konten" class="flex-1">
        @yield('content')
    </main>

    {{-- WhatsApp Floating Quick Pill --}}
    @if ($whatsapp !== null)
        <aside aria-label="Bantuan WhatsApp" class="fixed bottom-6 right-6 z-50">
            <a href="{{ $whatsapp }}" target="_blank" rel="noopener noreferrer"
               class="group flex items-center gap-2.5 rounded-full bg-emerald-600 px-4 py-3 text-white shadow-lg transition hover:bg-emerald-700 hover:shadow-xl hover:scale-105"
               aria-label="Konsultasi langsung melalui WhatsApp">
                <svg class="h-5 w-5 fill-current" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M12.031 2C6.494 2 2 6.494 2 12.031c0 1.808.483 3.513 1.328 5.002L2 22l5.127-1.323a10.007 10.007 0 0 0 4.904 1.284h.005c5.536 0 10.03-4.494 10.03-10.03 0-2.678-1.042-5.198-2.936-7.091A9.972 9.972 0 0 0 12.031 2zm5.875 14.288c-.247.697-1.242 1.294-1.722 1.378-.456.079-1.05.113-1.696-.095-.414-.133-.948-.31-1.637-.607-2.91-1.258-4.81-4.225-4.956-4.418-.146-.194-1.185-1.579-1.185-3.011 0-1.432.748-2.138 1.014-2.428.267-.291.583-.364.778-.364.194 0 .388.002.558.01.18.01.423-.069.66.5.247.596.845 2.062.919 2.213.073.151.122.327.024.523-.097.195-.146.317-.291.487-.146.17-.308.38-.44.51-.146.145-.3.303-.129.596.17.291.758 1.247 1.624 2.017 1.116.993 2.057 1.302 2.348 1.448.291.146.462.122.632-.073.17-.195.728-.85 1.02-1.142.291-.291.583-.243.972-.097.388.146 2.474 1.165 2.898 1.378.424.213.705.316.802.486.097.17.097.994-.15 1.691z"/>
                </svg>
                <span class="text-xs font-semibold tracking-wide hidden sm:inline">Tanya Kami</span>
            </a>
        </aside>
    @endif

    {{-- Professional Modern Footer --}}
    <footer class="border-t border-stone-200 bg-white">
        <div class="mx-auto grid max-w-6xl gap-10 px-4 py-16 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Col 1: Brand & Bio --}}
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('brand/wedly-mark-96.png') }}" alt="" width="38" height="38"
                         class="h-9 w-9 rounded-full bg-rose-50" decoding="async">
                    <span class="font-serif text-xl font-bold text-stone-900">{{ $siteName }}</span>
                </div>
                <p class="text-xs uppercase tracking-[0.2em] font-semibold text-rose-800">{{ $tagline }}</p>
                <p class="text-sm leading-relaxed text-stone-600">
                    Platform undangan pernikahan digital & wedding organizer terpadu. Membantu mempelai mewujudkan momen sakral yang anggun, tanpa kerumitan.
                </p>
            </div>

            {{-- Col 2: Produk & Fitur --}}
            <nav aria-label="Menu Produk" class="text-sm">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-stone-900">Layanan & Tema</p>
                <ul class="mt-4 space-y-2.5 text-stone-600">
                    <li><a class="transition hover:text-rose-900 hover:underline" href="{{ route('templates.index') }}">Katalog 13+ Tema Eksklusif</a></li>
                    <li><a class="transition hover:text-rose-900 hover:underline" href="{{ route('home') }}#contoh">Contoh Undangan Nyata</a></li>
                    <li><a class="transition hover:text-rose-900 hover:underline" href="{{ route('showcase.index') }}">Galeri Inspirasi Pasangan</a></li>
                    @if (App\Support\Marketing\WeddingServices::all() !== [])
                        <li><a class="transition hover:text-rose-900 hover:underline" href="{{ route('home') }}#layanan">Rias, Dekorasi & Foto WO</a></li>
                    @endif
                    <li><a class="transition hover:text-rose-900 hover:underline" href="{{ route('home') }}#harga">Paket Sekali Bayar</a></li>
                </ul>
            </nav>

            {{-- Col 3: Legal & Keamanan --}}
            <nav aria-label="Menu Legal" class="text-sm">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-stone-900">Keamanan & Legal</p>
                <ul class="mt-4 space-y-2.5 text-stone-600">
                    <li><a class="transition hover:text-rose-900 hover:underline" href="{{ route('legal.privacy') }}">Kebijakan Privasi Tamu</a></li>
                    <li><a class="transition hover:text-rose-900 hover:underline" href="{{ route('legal.terms') }}">Syarat & Ketentuan Layanan</a></li>
                    <li><a class="transition hover:text-rose-900 hover:underline" href="{{ route('home') }}#faq">Pusat Bantuan & FAQ</a></li>
                    <li><span class="inline-flex items-center gap-1.5 text-xs text-emerald-800 font-medium mt-1">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        Privasi data tamu 100% terjaga
                    </span></li>
                </ul>
            </nav>

            {{-- Col 4: Kontak & Studio --}}
            <div class="text-sm">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-stone-900">Hubungi Studio</p>
                <ul class="mt-4 space-y-2 text-stone-600">
                    <li class="font-semibold text-stone-900">{{ config('site.operator') }}</li>
                    <li><a class="transition hover:text-rose-900 hover:underline" href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a></li>
                    @if ($phoneHref !== null)
                        <li><a class="transition hover:text-rose-900 hover:underline" href="tel:{{ $phoneHref }}">{{ $phoneDisplay }}</a></li>
                    @endif
                    @if ($whatsapp !== null)
                        <li>
                            <a class="inline-flex items-center gap-1.5 font-medium text-emerald-700 transition hover:underline" href="{{ $whatsapp }}" target="_blank" rel="noopener noreferrer">
                                Chat WhatsApp: {{ $phoneDisplay }}
                            </a>
                        </li>
                    @endif
                    <li class="text-xs text-stone-500 pt-1">Jam Operasional: {{ $contact['hours'] }}</li>
                    @if ($addressLines !== [])
                        <li class="pt-2 text-xs leading-relaxed text-stone-500">
                            @foreach ($addressLines as $line)
                                {{ $line }}@if (! $loop->last)<br>@endif
                            @endforeach
                            @if ($mapsUrl !== '')
                                <br><a class="text-rose-800 underline hover:text-rose-900" href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer">Petunjuk Arah Maps &rarr;</a>
                            @endif
                        </li>
                    @endif
                </ul>
            </div>
        </div>

        {{-- Bottom Copyright Bar --}}
        <div class="border-t border-stone-200 bg-stone-50">
            <div class="mx-auto flex max-w-6xl flex-col gap-3 px-4 py-6 text-xs text-stone-500 sm:flex-row sm:items-center sm:justify-between">
                <p>&copy; {{ now()->year }} {{ config('site.operator') }} &middot; {{ config('site.credit') }}</p>
                <div class="flex items-center gap-6">
                    <a class="hover:text-stone-800" href="{{ route('legal.privacy') }}">Privasi</a>
                    <a class="hover:text-stone-800" href="{{ route('legal.terms') }}">Ketentuan</a>
                    <a class="hover:text-stone-800" href="{{ route('login') }}">Masuk</a>
                    <a class="font-semibold text-rose-800 hover:underline" href="{{ route('register') }}">Buat Undangan Gratis</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
