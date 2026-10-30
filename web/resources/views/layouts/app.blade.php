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
    <header class="border-b border-stone-200 bg-white">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-3">
            <a href="{{ route('dashboard') }}" class="font-serif text-lg font-semibold tracking-tight">
                {{ config('app.name') }}
            </a>

            @auth
                <nav class="flex flex-wrap items-center gap-4 text-sm">
                    <a href="{{ route('dashboard') }}" class="text-stone-600 hover:text-stone-900">Dashboard</a>
                    <a href="{{ route('weddings.index') }}" class="text-stone-600 hover:text-stone-900">Undangan</a>
                    <a href="{{ route('weddings.create') }}" class="rounded-md bg-rose-800 px-3 py-1.5 font-medium text-white hover:bg-rose-900">
                        Buat undangan
                    </a>
                    <span class="text-stone-400">{{ auth()->user()->email }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-stone-600 underline-offset-4 hover:text-stone-900 hover:underline">
                            Keluar
                        </button>
                    </form>
                </nav>
            @endauth
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-8">
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
</body>
</html>
