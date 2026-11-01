@php
    $countdown = $invitation->countdown();
    $calendarUrl = null;

    if ($countdown !== null && ! ($preview ?? false)) {
        $calendarUrl = ($demo ?? false)
            ? route('templates.calendar', ['template' => $invitation->templateSlug])
            : route('invitations.calendar', ['slug' => $invitation->slug]);
    }

    $googleUrl = $calendarUrl === null ? null : \App\Support\Invitations\CalendarFile::googleUrl($invitation);
    $units = [
        ['key' => 'days', 'label' => 'Hari'],
        ['key' => 'hours', 'label' => 'Jam'],
        ['key' => 'minutes', 'label' => 'Menit'],
        ['key' => 'seconds', 'label' => 'Detik'],
    ];
@endphp

@if ($countdown !== null)
    <section class="pw pw-count" aria-label="Hitung mundur menuju {{ $countdown['label'] }}">
        <p class="pw-eyebrow">Menghitung Hari</p>
        <h2 class="pw-title">{{ $countdown['label'] }}</h2>

        <div class="pw-count__grid" data-countdown data-target="{{ $countdown['target_iso'] }}">
            @foreach ($units as $unit)
                <div class="pw-count__cell">
                    <span class="pw-count__num" data-unit="{{ $unit['key'] }}">{{ str_pad((string) $countdown[$unit['key']], 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="pw-count__unit">{{ $unit['label'] }}</span>
                </div>
            @endforeach
        </div>

        @if ($calendarUrl !== null)
            <p class="pw-count__actions">
                <a class="pw-btn pw-btn--solid" href="{{ $calendarUrl }}" download>Simpan tanggal</a>
                @if ($googleUrl !== null)
                    <a class="pw-btn" href="{{ $googleUrl }}" target="_blank" rel="noopener noreferrer">Google Calendar</a>
                @endif
            </p>
        @endif
    </section>

    <style>
        .pw-count__grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .5rem;
            max-width: 26rem;
            margin: 1.35rem auto 0;
        }
        .pw-count__cell {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: .2rem;
            padding: .7rem .2rem .55rem;
            border: 1px solid var(--pw-line, rgba(0, 0, 0, .16));
            border-radius: var(--pw-radius, 10px);
            background: var(--pw-surface, transparent);
        }
        .pw-count__num {
            font-family: var(--pw-numeric, inherit);
            font-size: clamp(1.35rem, 6vw, 1.85rem);
            line-height: 1;
            font-variant-numeric: tabular-nums;
            letter-spacing: .02em;
        }
        .pw-count__unit {
            font-size: .58rem;
            letter-spacing: .18em;
            text-transform: uppercase;
            opacity: .68;
        }
        .pw-count__actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: .55rem;
            margin: 1.2rem 0 0;
        }
        /* The counter stops ticking at zero instead of showing negative time. */
        .pw-count__grid[data-finished] .pw-count__num { opacity: .55; }
    </style>
@endif
