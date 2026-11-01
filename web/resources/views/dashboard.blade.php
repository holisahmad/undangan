@extends('layouts.app')

@section('title', 'Dashboard Mempelai')

@section('content')
    <div class="space-y-8">
        {{-- Welcome Banner --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-rose-950 via-rose-900 to-rose-850 p-6 sm:p-8 text-white shadow-lg">
            <div class="relative z-10 flex flex-wrap items-center justify-between gap-6">
                <div class="max-w-xl">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-rose-200 backdrop-blur">
                        ✦ Ruang Kendali Pernikahan
                    </span>
                    <h1 class="mt-3 font-serif text-2xl sm:text-3xl font-bold tracking-tight text-white">
                        Selamat Datang, {{ auth()->user()->name }}
                    </h1>
                    <p class="mt-2 text-sm text-rose-100/90 leading-relaxed">
                        Kelola draf undangan, pantau daftar tamu dan RSVP, serta atur galeri foto hari bahagia Anda dalam satu tempat yang aman dan terpadu.
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('weddings.create') }}"
                       class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-3 text-xs sm:text-sm font-bold text-rose-950 shadow-md transition hover:bg-rose-50 hover:scale-105">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Buat Undangan Baru
                    </a>
                </div>
            </div>
        </div>

        {{-- Main Content / Undangan List --}}
        <div>
            <div class="flex items-center justify-between pb-4 border-b border-stone-200">
                <div>
                    <h2 class="font-serif text-xl font-bold text-stone-900">Daftar Acara &amp; Undangan Anda</h2>
                    <p class="text-xs sm:text-sm text-stone-500 mt-0.5">
                        Perubahan draf tetap aman dan tidak memengaruhi tautan tamu sampai Anda menerbitkan ulang.
                    </p>
                </div>
                <a href="{{ route('templates.index') }}" class="text-xs font-semibold text-rose-800 hover:text-rose-950 hover:underline hidden sm:inline">
                    Katalog Tema &rarr;
                </a>
            </div>

            @if ($weddings->isEmpty())
                <div class="mt-6 rounded-2xl border-2 border-dashed border-stone-300 bg-white p-10 text-center">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-rose-50 text-rose-800">
                        <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                        </svg>
                    </div>
                    <h3 class="mt-4 font-serif text-lg font-bold text-stone-900">Belum Ada Undangan yang Dibuat</h3>
                    <p class="mt-1 text-sm text-stone-600 max-w-md mx-auto leading-relaxed">
                        Mulai langkah indah Anda. Pilih desain template mewah, lengkapi data mempelai, dan nikmati kemudahan mengelola undangan pernikahan.
                    </p>
                    <a href="{{ route('weddings.create') }}"
                       class="btn-primary mt-6 text-sm py-3 px-6">
                        Buat Undangan Pertama Saya
                    </a>
                </div>
            @else
                <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($weddings as $wedding)
                        @include('weddings.partials.card', ['wedding' => $wedding])
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $weddings->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
