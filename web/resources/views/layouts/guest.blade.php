@php
    // The login and password screens are where a locked-out customer lands, so
    // the studio's own channels belong on the same card.
    $supportEmail = (string) config('site.contact.email');
    $supportWhatsapp = App\Support\Contact\PhoneNumber::whatsappLink((string) config('site.contact.whatsapp'));
@endphp
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
            <a href="{{ route('home') }}" class="inline-flex flex-col items-center gap-2">
                <img src="{{ asset('brand/wedly-mark-96.png') }}" alt="" width="48" height="48"
                     class="h-12 w-12 rounded-full" decoding="async">
                <span class="font-serif text-2xl font-semibold tracking-tight">{{ config('app.name') }}</span>
            </a>
            <p class="mt-1 text-sm text-stone-500">{{ config('site.tagline') }}</p>
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

        <p class="mt-6 text-center text-xs text-stone-500">
            Butuh bantuan?
            @if ($supportWhatsapp !== null)
                <a class="underline hover:text-stone-800" href="{{ $supportWhatsapp }}" target="_blank" rel="noopener noreferrer">WhatsApp</a>
                &middot;
            @endif
            <a class="underline hover:text-stone-800" href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>
        </p>
    </div>
</body>
</html>
