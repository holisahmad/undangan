@php
    $status = $wedding->lifecycle_status;
    $statusClasses = match ($status->value) {
        'published' => 'bg-emerald-100 text-emerald-800',
        'unpublished' => 'bg-amber-100 text-amber-800',
        'expired' => 'bg-stone-200 text-stone-700',
        default => 'bg-rose-100 text-rose-800',
    };
    $names = $wedding->partners
        ->map(fn ($partner) => $partner->nickname ?: $partner->display_name)
        ->filter()
        ->implode(' & ');
    $firstEvent = $wedding->events->first();
@endphp

<article class="flex h-full flex-col rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
    <div class="flex items-start justify-between gap-3">
        <h2 class="font-serif text-lg font-semibold leading-tight">{{ $wedding->internal_title }}</h2>
        <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium {{ $statusClasses }}">
            {{ $status->label() }}
        </span>
    </div>

    <p class="mt-2 text-sm text-stone-600">
        {{ $names !== '' ? $names : 'Data mempelai belum lengkap' }}
    </p>

    <p class="mt-1 text-sm text-stone-500">
        @if ($firstEvent?->starts_at)
            {{ $firstEvent->starts_at->copy()->setTimezone($firstEvent->timezone)->locale(app()->getLocale())->translatedFormat('l, d F Y') }}
        @else
            Acara belum ditambahkan
        @endif
    </p>

    <div class="mt-4 flex-1"></div>

    <div class="flex flex-wrap items-center gap-3 text-sm">
        <a href="{{ route('weddings.show', $wedding) }}" class="font-medium text-rose-800 hover:underline">Kelola</a>
        <a href="{{ route('weddings.preview', $wedding) }}" class="text-stone-600 hover:underline">Pratinjau</a>
        @if ($wedding->slug)
            <span class="text-xs text-stone-400">/u/{{ $wedding->slug }}</span>
        @endif
    </div>
</article>
