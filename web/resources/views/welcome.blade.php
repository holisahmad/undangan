<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} — Undangan pernikahan digital</title>
    @include('partials.assets')
</head>
<body class="min-h-full bg-stone-50 text-stone-900 antialiased">
    <header class="border-b border-stone-200 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
            <span class="font-serif text-lg font-semibold">{{ config('app.name') }}</span>
            <nav class="flex items-center gap-4 text-sm">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-md bg-rose-800 px-4 py-2 font-medium text-white hover:bg-rose-900">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="text-stone-600 hover:text-stone-900">Masuk</a>
                    <a href="{{ route('register') }}" class="rounded-md bg-rose-800 px-4 py-2 font-medium text-white hover:bg-rose-900">
                        Buat akun
                    </a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-3xl px-4 py-16">
        <p class="text-xs uppercase tracking-[0.28em] text-rose-800">Undangan pernikahan digital</p>
        <h1 class="mt-3 font-serif text-3xl font-semibold leading-tight sm:text-4xl">
            Susun undangan, terbitkan tautan, kelola dengan tenang.
        </h1>
        <p class="mt-4 text-stone-600">
            Buat draf undangan dari template, isi data mempelai dan acara, lalu terbitkan versi yang beku
            untuk dibagikan. Perubahan draf tidak akan mengubah tautan yang sudah dibagikan sampai Anda
            menerbitkan ulang.
        </p>

        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ auth()->check() ? route('weddings.create') : route('register') }}"
               class="rounded-md bg-rose-800 px-5 py-2.5 text-sm font-medium text-white hover:bg-rose-900">
                Mulai buat undangan
            </a>
            <a href="{{ auth()->check() ? route('dashboard') : route('login') }}"
               class="rounded-md border border-stone-300 bg-white px-5 py-2.5 text-sm font-medium hover:border-stone-400">
                {{ auth()->check() ? 'Ke dashboard' : 'Masuk ke akun' }}
            </a>
        </div>
    </main>
</body>
</html>
