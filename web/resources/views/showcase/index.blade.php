@extends('layouts.marketing')

@section('title', 'Inspirasi undangan &middot; '.config('app.name'))

@section('meta')
    <meta name="description" content="Kumpulan undangan pernikahan yang dibagikan atas izin mempelai: lihat template, susunan acara, dan alur RSVP sebelum membuat milik Anda.">
    <link rel="canonical" href="{{ route('showcase.index') }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="Inspirasi undangan &middot; {{ config('app.name') }}">
    <meta property="og:description" content="Undangan pernikahan yang dibagikan atas izin mempelai.">
    <meta property="og:url" content="{{ route('showcase.index') }}">
    <meta property="og:locale" content="id_ID">
@endsection

@section('content')
    <section class="mx-auto max-w-6xl px-4 py-16">
        <p class="text-xs uppercase tracking-[0.28em] text-rose-800">Portofolio</p>
        <h1 class="mt-3 font-serif text-3xl font-semibold sm:text-4xl">Inspirasi dari pasangan lain</h1>
        <p class="mt-4 max-w-2xl text-sm text-stone-600">
            Hanya undangan yang pemiliknya mengaktifkan portofolio yang tampil di sini. Setiap halaman memakai
            data terbit yang sama dengan yang dilihat tamu, lengkap dengan penanda portofolio.
        </p>

        @if ($cards === [])
            <div class="mt-10 rounded-lg border border-stone-200 bg-white p-8 text-sm text-stone-600">
                <p>Belum ada undangan yang dibagikan untuk portofolio.</p>
                <p class="mt-2">
                    Lihat <a class="font-medium text-rose-800 underline-offset-4 hover:underline" href="{{ route('home') }}#contoh">contoh template</a>
                    untuk gambaran desainnya.
                </p>
            </div>
        @else
            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($cards as $card)
                    <a href="{{ $card['url'] }}"
                       class="group flex flex-col rounded-lg border border-stone-200 bg-white p-6 shadow-sm transition hover:border-rose-300 hover:shadow">
                        <p class="text-xs uppercase tracking-[0.2em] text-stone-500">{{ $card['template'] !== '' ? $card['template'] : 'Template' }}</p>
                        <p class="mt-2 font-serif text-xl font-semibold group-hover:text-rose-800">{{ $card['headline'] }}</p>
                        @if ($card['when'])
                            <p class="mt-2 text-sm text-stone-600">{{ $card['when'] }}</p>
                        @endif
                        @if ($card['venue'])
                            <p class="mt-1 text-sm text-stone-500">{{ $card['venue'] }}</p>
                        @endif
                        <span class="mt-4 text-sm font-medium text-rose-800">Buka undangan &rarr;</span>
                    </a>
                @endforeach
            </div>
        @endif

        <div class="mt-10 rounded-lg border border-stone-200 bg-white p-6">
            <h2 class="font-serif text-lg font-semibold">Ingin undangan Anda tampil di sini?</h2>
            <p class="mt-2 text-sm text-stone-600">
                Aktifkan portofolio dari halaman undangan Anda setelah terbit. Anda dapat menariknya kapan saja,
                dan tidak ada data tamu yang ikut ditampilkan.
            </p>
            <a href="{{ auth()->check() ? route('weddings.index') : route('register') }}"
               class="mt-4 inline-block rounded-md bg-rose-800 px-4 py-2 text-sm font-medium text-white hover:bg-rose-900">
                {{ auth()->check() ? 'Buka undangan saya' : 'Buat akun' }}
            </a>
        </div>
    </section>
@endsection
