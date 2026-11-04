@extends('layouts.app')

@section('title', 'Buat Undangan Baru')

@section('content')
    @php
        $preselectedSlug = request('template');
        $preselectedVersionId = request('template_version_id');
    @endphp

    <div class="max-w-5xl mx-auto">
        {{-- Step & Guidance Banner --}}
        <div class="mb-6 rounded-2xl bg-gradient-to-r from-rose-50 via-white to-amber-50 p-5 border border-rose-200/60 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-100 px-3 py-0.5 text-xs font-bold uppercase tracking-wider text-rose-800">
                        Langkah 1 dari 2
                    </span>
                    <h1 class="mt-2 font-serif text-2xl font-bold text-stone-900">Mulai Buat Undangan Baru</h1>
                    <p class="mt-1 text-xs sm:text-sm text-stone-600">
                        Beri nama internal acara dan pilih desain awal yang Anda sukai.
                    </p>
                </div>
                <div class="rounded-xl bg-white/80 border border-stone-200 px-4 py-2.5 text-xs text-stone-600 shadow-sm max-w-xs">
                    <span class="font-semibold text-rose-900 block mb-0.5">✦ Bebas Ganti Template</span>
                    Template dapat diganti kapan saja di halaman editor sebelum posting/terbit tanpa menghapus data yang telah diisi.
                </div>
            </div>
        </div>

        @if ($templates->isEmpty())
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 text-sm text-amber-900 text-center">
                Belum ada template aktif saat ini.
            </div>
        @else
            <form method="POST" action="{{ route('weddings.store') }}" class="space-y-8">
                @csrf

                {{-- 1. Nama Internal --}}
                <div class="rounded-2xl border border-stone-200 bg-white p-5 sm:p-6 shadow-sm">
                    <label for="internal_title" class="block font-serif text-base font-bold text-stone-900">
                        Nama Acara / Judul Undangan
                    </label>
                    <p class="mt-1 text-xs text-stone-500">
                        Nama internal untuk memudahkan Anda mengelola undangan di dashboard (tidak terlihat oleh tamu undangan).
                    </p>
                    <input id="internal_title" name="internal_title" type="text" required maxlength="160"
                           value="{{ old('internal_title') }}"
                           placeholder="Contoh: Pernikahan Ayu & Bima"
                           class="mt-3 w-full rounded-xl border border-stone-300 px-4 py-2.5 text-sm shadow-sm transition focus:border-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-200">
                </div>

                {{-- 2. Pilihan Desain Template --}}
                <div class="rounded-2xl border border-stone-200 bg-white p-5 sm:p-6 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-stone-100 pb-4">
                        <div>
                            <h2 class="font-serif text-base font-bold text-stone-900">Pilih Desain Awal</h2>
                            <p class="mt-0.5 text-xs text-stone-500">
                                Klik salah satu kartu tema di bawah. Paket pesanan dipilih nanti setelah data lengkap disusun.
                            </p>
                        </div>
                        <a href="{{ route('templates.index') }}" target="_blank" class="text-xs font-semibold text-rose-800 hover:underline">
                            Buka Katalog Lengkap &rarr;
                        </a>
                    </div>

                    {{-- Category Filter Chips --}}
                    <div class="mt-4 flex flex-wrap items-center gap-1.5" id="template-filter-bar">
                        <button type="button" data-filter="all" class="filter-chip rounded-full bg-stone-900 px-3 py-1 text-xs font-semibold text-white transition hover:bg-stone-800">
                            Semua ({{ count($templates) }})
                        </button>
                        <button type="button" data-filter="modern" class="filter-chip rounded-full bg-stone-100 px-3 py-1 text-xs font-medium text-stone-600 transition hover:bg-stone-200">
                            Modern
                        </button>
                        <button type="button" data-filter="classic" class="filter-chip rounded-full bg-stone-100 px-3 py-1 text-xs font-medium text-stone-600 transition hover:bg-stone-200">
                            Classic
                        </button>
                        <button type="button" data-filter="floral" class="filter-chip rounded-full bg-stone-100 px-3 py-1 text-xs font-medium text-stone-600 transition hover:bg-stone-200">
                            Floral
                        </button>
                        <button type="button" data-filter="traditional" class="filter-chip rounded-full bg-stone-100 px-3 py-1 text-xs font-medium text-stone-600 transition hover:bg-stone-200">
                            Tradisional
                        </button>
                    </div>

                    {{-- Compact Template Grid --}}
                    <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3" id="template-cards-grid">
                        @foreach ($templates as $index => $version)
                            @php
                                $template = $version->template;
                                $theme = App\Support\Templates\TemplatePresentation::card($template);
                                $badge = App\Support\Templates\TemplatePresentation::badgeLabel($template->badge);
                                $tierLabel = App\Support\Templates\TemplatePresentation::tierLabel($template->tier);
                                $categoryKey = strtolower($template->category ?? '');
                                $isSelected = old('template_version_id') === $version->getKey()
                                    || ($preselectedVersionId && $preselectedVersionId == $version->getKey())
                                    || ($preselectedSlug && $preselectedSlug === $template->slug)
                                    || (!old('template_version_id') && !$preselectedSlug && !$preselectedVersionId && $index === 0);
                            @endphp

                            <label class="template-select-card group relative flex flex-col justify-between overflow-hidden rounded-xl border border-stone-200 bg-white p-3.5 shadow-sm transition-all duration-200 hover:border-rose-400 hover:shadow-md cursor-pointer has-[:checked]:border-rose-700 has-[:checked]:ring-2 has-[:checked]:ring-rose-200 has-[:checked]:bg-rose-50/20"
                                   data-category="{{ $categoryKey }}">
                                {{-- Theme Color Header --}}
                                <div class="absolute inset-x-0 top-0 h-1.5"
                                     style="background: linear-gradient(90deg, {{ $theme['paper'] }}, {{ $theme['accent'] }})"></div>

                                <div>
                                    <div class="flex items-start justify-between gap-2 pt-1">
                                        <div class="flex items-center gap-2">
                                            <input type="radio" name="template_version_id" value="{{ $version->getKey() }}"
                                                   @checked($isSelected)
                                                   class="h-4 w-4 border-stone-300 text-rose-800 focus:ring-rose-700">
                                            <span class="font-serif text-sm font-bold text-stone-900 group-hover:text-rose-950">
                                                {{ $template->name }}
                                            </span>
                                        </div>
                                        @if ($badge !== null)
                                            <span class="rounded bg-rose-50 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-rose-800">
                                                {{ $badge }}
                                            </span>
                                        @endif
                                    </div>

                                    <p class="mt-2 text-xs text-stone-500 line-clamp-2 leading-relaxed">
                                        {{ $template->description ?? 'Template undangan pernikahan elegan.' }}
                                    </p>
                                </div>

                                <div class="mt-3 pt-2.5 border-t border-stone-100 flex items-center justify-between gap-1 text-[11px]">
                                    <div class="flex flex-wrap gap-1">
                                        <span class="rounded bg-stone-100 px-1.5 py-0.5 text-[10px] font-medium text-stone-600">
                                            {{ App\Support\Templates\TemplatePresentation::categoryLabel($template->category) }}
                                        </span>
                                        @if ($template->tier !== App\Models\InvitationTemplate::TIER_ESSENTIAL)
                                            <span class="rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold text-amber-800">
                                                {{ $tierLabel }}
                                            </span>
                                        @endif
                                    </div>
                                    <a href="{{ route('templates.demo', ['template' => $template->slug]) }}"
                                       target="_blank"
                                       rel="noopener"
                                       class="font-semibold text-rose-800 hover:underline flex items-center gap-0.5">
                                        Lihat contoh &rarr;
                                    </a>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Action Submit --}}
                <div class="flex items-center gap-4 pt-2">
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-rose-900 px-6 py-3 text-sm font-bold text-white shadow-md transition hover:bg-rose-950 hover:scale-[1.02]">
                        Lanjut Isi Data Mempelai &rarr;
                    </button>
                    <a href="{{ route('dashboard') }}" class="text-sm font-medium text-stone-600 hover:underline">
                        Batal
                    </a>
                </div>
            </form>
        @endif
    </div>

    {{-- Interactive Category Filter Script (Vanilla JS) --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var filterBtns = document.querySelectorAll('#template-filter-bar .filter-chip');
            var cards = document.querySelectorAll('#template-cards-grid .template-select-card');

            filterBtns.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var filter = btn.getAttribute('data-filter');

                    // Update active button state
                    filterBtns.forEach(function (b) {
                        b.classList.remove('bg-stone-900', 'text-white');
                        b.classList.add('bg-stone-100', 'text-stone-600');
                    });
                    btn.classList.remove('bg-stone-100', 'text-stone-600');
                    btn.classList.add('bg-stone-900', 'text-white');

                    // Filter cards
                    cards.forEach(function (card) {
                        var cat = card.getAttribute('data-category') || '';
                        if (filter === 'all' || cat.indexOf(filter) !== -1) {
                            card.style.display = 'flex';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            });
        });
    </script>
@endsection
