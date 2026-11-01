@php
    $status = $wedding->lifecycle_status;
    $statusBadge = match ($status->value) {
        'published' => ['class' => 'bg-emerald-50 text-emerald-700 border-emerald-200/80', 'dot' => 'bg-emerald-500'],
        'unpublished' => ['class' => 'bg-amber-50 text-amber-700 border-amber-200/80', 'dot' => 'bg-amber-500'],
        'expired' => ['class' => 'bg-stone-100 text-stone-600 border-stone-200', 'dot' => 'bg-stone-400'],
        default => ['class' => 'bg-rose-50 text-rose-700 border-rose-200/80', 'dot' => 'bg-rose-500'],
    };
    $names = $wedding->partners
        ->map(fn ($partner) => $partner->nickname ?: $partner->display_name)
        ->filter()
        ->implode(' & ');
    $firstEvent = $wedding->events->first();
@endphp

<article class="group relative flex h-full flex-col justify-between overflow-hidden rounded-2xl border border-stone-200/80 bg-white p-5 shadow-sm transition-all duration-300 hover:border-rose-300 hover:shadow-md">
    <div>
        {{-- Card Header: Title & Status Pill --}}
        <div class="flex items-start justify-between gap-3">
            <h2 class="font-serif text-lg font-bold text-stone-900 group-hover:text-rose-950 transition">
                {{ $wedding->internal_title }}
            </h2>
            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $statusBadge['class'] }}">
                <span class="h-1.5 w-1.5 rounded-full {{ $statusBadge['dot'] }}"></span>
                {{ $status->label() }}
            </span>
        </div>

        {{-- Couple Names --}}
        <div class="mt-3 flex items-center gap-2 text-sm font-medium text-stone-700">
            <svg class="h-4 w-4 text-rose-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
            </svg>
            <span class="truncate">{{ $names !== '' ? $names : 'Data mempelai belum lengkap' }}</span>
        </div>

        {{-- Event Date --}}
        <div class="mt-2 flex items-center gap-2 text-xs text-stone-500">
            <svg class="h-3.5 w-3.5 text-stone-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            <span>
                @if ($firstEvent?->starts_at)
                    {{ $firstEvent->starts_at->copy()->setTimezone($firstEvent->timezone)->locale(app()->getLocale())->translatedFormat('l, d F Y') }}
                @else
                    Acara belum ditambahkan
                @endif
            </span>
        </div>
    </div>

    {{-- Card Footer Actions --}}
    <div class="mt-5 pt-4 border-t border-stone-100 flex flex-wrap items-center justify-between gap-2 text-xs">
        <div class="flex items-center gap-2">
            <a href="{{ route('weddings.show', $wedding) }}"
               class="inline-flex items-center gap-1.5 rounded-lg bg-stone-900 px-3 py-1.5 font-semibold text-white shadow-sm transition hover:bg-stone-800">
                Kelola
                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            </a>
            <a href="{{ route('weddings.preview', $wedding) }}"
               class="inline-flex items-center gap-1 rounded-lg border border-stone-200 bg-white px-3 py-1.5 font-medium text-stone-700 transition hover:bg-stone-50">
                <svg class="h-3 w-3 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                Pratinjau
            </a>
        </div>

        @if ($wedding->slug)
            <span class="rounded bg-stone-100 px-2 py-0.5 text-[11px] font-mono text-stone-500">
                /u/{{ $wedding->slug }}
            </span>
        @endif
    </div>
</article>
