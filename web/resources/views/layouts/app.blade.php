@php
    // A customer stuck mid-order needs a way out of the app; the studio's real
    // channels belong in the footer, not only on the marketing page.
    $supportEmail = (string) config('site.contact.email');
    $supportWhatsapp = App\Support\Contact\PhoneNumber::whatsappLink((string) config('site.contact.whatsapp'));
    $supportPhone = App\Support\Contact\PhoneNumber::display((string) config('site.contact.phone'));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') &middot; {{ config('app.name') }}</title>
    @include('partials.assets')
</head>
<body class="min-h-full bg-stone-50 text-stone-900 antialiased">
    <header class="sticky top-0 z-40 border-b border-stone-200 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-2.5 sm:py-3">
            <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-2">
                <img src="{{ asset('brand/wedly-mark-96.png') }}" alt="" width="28" height="28"
                     class="h-7 w-7 shrink-0 rounded-full" decoding="async">
                <span class="min-w-0 truncate font-serif text-lg font-semibold tracking-tight">{{ config('app.name') }}</span>
            </a>

            @auth
                <div class="flex items-center gap-2">
                    <a href="{{ route('weddings.create') }}"
                       class="hidden rounded-md bg-rose-800 px-3 py-1.5 text-sm font-medium text-white hover:bg-rose-900 sm:inline-block">
                        Buat undangan
                    </a>

                    {{-- Desktop navigation: everything visible, exactly as before. --}}
                    <nav aria-label="Navigasi utama" class="hidden items-center gap-4 text-sm lg:flex">
                        <a href="{{ route('dashboard') }}" class="text-stone-600 hover:text-stone-900">Dashboard</a>
                        <a href="{{ route('weddings.index') }}" class="text-stone-600 hover:text-stone-900">Undangan</a>
                        <a href="{{ route('orders.index') }}" class="text-stone-600 hover:text-stone-900">Pesanan</a>
                        <a href="{{ route('security.edit') }}" class="text-stone-600 hover:text-stone-900">Keamanan</a>
                        @if (auth()->user()->isAdmin())
                            <a href="{{ route('admin.weddings.index') }}" class="text-stone-600 hover:text-stone-900">Admin</a>
                        @endif
                        <span class="max-w-[12rem] truncate text-stone-400">{{ auth()->user()->email }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-stone-600 underline-offset-4 hover:text-stone-900 hover:underline">
                                Keluar
                            </button>
                        </form>
                    </nav>

                    {{-- Phone and tablet navigation: a <details> menu works without
                         JavaScript and keeps 44px touch targets. --}}
                    <details class="relative lg:hidden" data-mobile-menu>
                        <summary aria-label="Buka menu"
                                 class="flex h-10 w-10 list-none cursor-pointer items-center justify-center rounded-md border border-stone-300 text-stone-700 marker:content-none hover:border-stone-400 [&::-webkit-details-marker]:hidden">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" aria-hidden="true">
                                <path d="M3 6h14M3 10h14M3 14h14" />
                            </svg>
                        </summary>

                        <div class="absolute right-0 top-full z-50 mt-2 w-60 rounded-lg border border-stone-200 bg-white p-2 text-sm shadow-lg">
                            <a href="{{ route('dashboard') }}" class="block rounded-md px-3 py-2.5 text-stone-700 hover:bg-stone-100">Dashboard</a>
                            <a href="{{ route('weddings.index') }}" class="block rounded-md px-3 py-2.5 text-stone-700 hover:bg-stone-100">Undangan</a>
                            <a href="{{ route('orders.index') }}" class="block rounded-md px-3 py-2.5 text-stone-700 hover:bg-stone-100">Pesanan</a>
                            <a href="{{ route('security.edit') }}" class="block rounded-md px-3 py-2.5 text-stone-700 hover:bg-stone-100">Keamanan</a>
                            @if (auth()->user()->isAdmin())
                                <a href="{{ route('admin.weddings.index') }}" class="block rounded-md px-3 py-2.5 text-stone-700 hover:bg-stone-100">Admin</a>
                            @endif

                            <a href="{{ route('weddings.create') }}"
                               class="mt-1 block rounded-md bg-rose-800 px-3 py-2.5 text-center font-medium text-white hover:bg-rose-900">
                                Buat undangan
                            </a>

                            <p class="mt-2 truncate border-t border-stone-100 px-3 pt-2 text-xs text-stone-500">
                                {{ auth()->user()->email }}
                            </p>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="mt-1 block w-full rounded-md px-3 py-2.5 text-left text-stone-700 hover:bg-stone-100">
                                    Keluar
                                </button>
                            </form>
                        </div>
                    </details>
                </div>
            @endauth
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-6 sm:py-8">
        @if (session('status'))
            <div class="mb-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900" role="alert">
                <p class="font-medium">Periksa kembali data berikut:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    @auth
        <footer class="mt-4 border-t border-stone-200 bg-white">
            <div class="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-5 text-xs text-stone-500 sm:flex-row sm:items-center sm:justify-between">
                <p>Butuh bantuan? {{ config('site.contact.hours') }}.</p>
                <p class="flex flex-wrap gap-4">
                    @if ($supportWhatsapp !== null)
                        <a class="underline hover:text-stone-800" href="{{ $supportWhatsapp }}" target="_blank" rel="noopener noreferrer">
                            WhatsApp {{ $supportPhone }}
                        </a>
                    @endif
                    <a class="underline hover:text-stone-800" href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>
                    <a class="underline hover:text-stone-800" href="{{ route('legal.terms') }}">Syarat &amp; ketentuan</a>
                </p>
            </div>
        </footer>
    @endauth
</body>
</html>
