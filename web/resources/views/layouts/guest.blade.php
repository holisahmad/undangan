<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Masuk') &middot; {{ config('app.name') }}</title>
    @include('partials.assets')
</head>
<body class="flex min-h-full items-center justify-center bg-stone-100 px-4 py-10 text-stone-900 antialiased">
    <div class="w-full max-w-md">
        <div class="mb-6 text-center">
            <a href="{{ route('home') }}" class="font-serif text-2xl font-semibold tracking-tight">{{ config('app.name') }}</a>
            <p class="mt-1 text-sm text-stone-500">Undangan pernikahan digital.</p>
        </div>

        <div class="rounded-lg border border-stone-200 bg-white p-6 shadow-sm">
            @if (session('status'))
                <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-900" role="status">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-900" role="alert">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </div>
    </div>
</body>
</html>
