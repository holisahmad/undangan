@php
    $version = $version ?? $template->publishedVersion();
    $demoUrl = $version === null ? null : route('templates.demo', ['template' => $template->slug]);
    $useUrl = route('weddings.create', ['template' => $template->slug]);

    // Colour, category wording and the package note are catalogue data that
    // lives with the renderer, so a card never invents its own vocabulary.
    $theme = App\Support\Templates\TemplatePresentation::cardTheme($template);
    $badge = App\Support\Templates\TemplatePresentation::badgeLabel($template->badge);
    $category = App\Support\Templates\TemplatePresentation::categoryLabel($template->category);
    $tier = App\Support\Templates\TemplatePresentation::tierLabel($template->tier);
    $note = $unlockNotes[$template->slug] ?? null;
@endphp

<article class="group relative flex h-full flex-col overflow-hidden rounded-2xl border border-stone-200/90 bg-white shadow-sm transition-all duration-300 hover:border-rose-300 hover:shadow-xl hover:-translate-y-1">
    {{-- Color swatch top bar --}}
    <div class="h-1.5 w-full shrink-0 {{ $theme['swatch'] }}"></div>

    @if ($badge !== null)
        <span class="absolute right-3 top-4 z-10 rounded-full bg-white/95 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-rose-800 shadow-md ring-1 ring-rose-100">
            ✦ {{ $badge }}
        </span>
    @endif

    {{-- Mini invitation preview --}}
    <div class="relative aspect-[4/5] border-b border-stone-100">
        <div class="flex h-full flex-col items-center justify-center gap-2 px-5 text-center {{ $theme['frame'] }} {{ $theme['font'] }}">
            {{-- Decorative top ornament --}}
            <span class="text-[9px] uppercase tracking-[0.32em] opacity-70">✦ ✦ ✦</span>
            <span class="text-[10px] uppercase tracking-[0.28em]">The Wedding Of</span>
            <span class="text-xl leading-tight font-serif">Ayu &amp; Bima</span>
            <span class="h-px w-12 {{ $theme['accent'] }} bg-current opacity-60"></span>
            <span class="text-[10px] uppercase tracking-[0.22em] {{ $theme['accent'] }}">12 · 12 · 2026</span>
            <span class="mt-1 text-[9px] uppercase tracking-[0.28em] opacity-50">Akad & Resepsi</span>
        </div>

        {{-- Hover overlay: "Buka Demo" and "Gunakan Desain" buttons --}}
        @if ($demoUrl !== null)
            <div class="absolute inset-0 flex flex-col items-center justify-center gap-2.5 bg-black/55 p-4 opacity-0 backdrop-blur-[3px] transition-all duration-300 group-hover:opacity-100">
                <a href="{{ $demoUrl }}"
                   class="flex w-full items-center justify-center gap-2 rounded-xl bg-white px-4 py-2 text-xs font-bold text-stone-900 shadow-md transition hover:bg-rose-50 hover:text-rose-900"
                   aria-label="Lihat demo tema {{ $template->name }}">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                    </svg>
                    Buka Demo
                </a>
                <a href="{{ $useUrl }}"
                   class="flex w-full items-center justify-center gap-2 rounded-xl bg-rose-800 px-4 py-2 text-xs font-bold text-white shadow-md transition hover:bg-rose-900">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Pilih Desain Ini
                </a>
            </div>
        @endif
    </div>

    {{-- Card footer --}}
    <div class="flex flex-1 flex-col gap-2.5 p-4">
        <div class="flex flex-wrap items-center gap-1.5">
            <span class="rounded-full bg-rose-50 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-rose-700">{{ $category }}</span>
            <span class="rounded-full bg-stone-100 px-2.5 py-0.5 text-[10px] font-medium uppercase tracking-[0.14em] text-stone-600">{{ $tier }}</span>
        </div>

        <h3 class="font-serif text-base font-bold text-stone-900 leading-snug">{{ $template->name }}</h3>
        <p class="flex-1 text-xs leading-relaxed text-stone-500">
            {{ $template->description ?? 'Template undangan pernikahan elegan.' }}
        </p>

        @if ($note !== null)
            <p class="flex items-center gap-1.5 rounded-lg bg-amber-50 px-3 py-2 text-[11px] font-medium text-amber-800">
                <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="5" y="11" width="14" height="9" rx="2" />
                    <path d="M8 11V8a4 4 0 0 1 8 0v3" />
                </svg>
                {{ $note }}
            </p>
        @endif

        <div class="mt-2 pt-2 border-t border-stone-100 flex items-center justify-between text-xs">
            @if ($demoUrl !== null)
                <a href="{{ $demoUrl }}"
                   class="flex items-center gap-1 font-semibold text-rose-800 transition hover:text-rose-950">
                    Lihat contoh
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m9 18 6-6-6-6"/>
                    </svg>
                </a>
            @endif
            <a href="{{ $useUrl }}" class="font-medium text-stone-600 hover:text-rose-800 hover:underline">
                Gunakan &rarr;
            </a>
        </div>
    </div>
</article>
