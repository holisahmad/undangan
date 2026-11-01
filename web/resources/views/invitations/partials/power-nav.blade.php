@php
    $navItems = [
        [
            'href' => '#mempelai',
            'label' => 'Mempelai',
            'icon' => '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>',
        ]
    ];

    if ($invitation->events !== []) {
        $navItems[] = [
            'href' => '#acara',
            'label' => 'Acara',
            'icon' => '<rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        ];
    }

    if ($invitation->giftIsVisible() && (($features['show_gift'] ?? true) !== false)) {
        $navItems[] = [
            'href' => '#hadiah',
            'label' => 'Hadiah',
            'icon' => '<rect x="3" y="8" width="18" height="14" rx="2"/><path d="M12 8v14M3 12h18"/><path d="M12 8H7.5a2.5 2.5 0 0 1 0-5C11 3 12 8 12 8zm0 0h4.5a2.5 2.5 0 0 0 0-5C13 3 12 8 12 8z"/>',
        ];
    }

    if (($wishes['total'] ?? 0) > 0) {
        $navItems[] = [
            'href' => '#ucapan',
            'label' => 'Ucapan',
            'icon' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        ];
    }
@endphp

@if (count($navItems) > 1)
    <nav class="pw-nav" aria-label="Bagian undangan">
        @foreach ($navItems as $item)
            <a class="pw-nav__link" href="{{ $item['href'] }}" title="{{ $item['label'] }}">
                <svg class="pw-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    {!! $item['icon'] !!}
                </svg>
                <span class="pw-nav__text">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <style>
        html { scroll-padding-top: .75rem; }
        @media (prefers-reduced-motion: no-preference) {
            html { scroll-behavior: smooth; }
        }
        body { padding-bottom: calc(76px + env(safe-area-inset-bottom, 0px)); }
        .pw-nav {
            position: fixed;
            z-index: 50;
            left: 50%;
            bottom: calc(12px + env(safe-area-inset-bottom, 0px));
            transform: translateX(-50%);
            display: flex;
            align-items: center;
            justify-content: space-around;
            gap: .25rem;
            width: calc(100% - 32px);
            max-width: 26rem;
            padding: .35rem .5rem;
            border: 1px solid var(--pw-line, rgba(255, 255, 255, .2));
            border-radius: 999px;
            background: var(--pw-nav-bg, rgba(25, 25, 30, .88));
            box-shadow: 0 10px 30px rgba(0, 0, 0, .25);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            transition: transform .3s ease, opacity .3s ease;
        }
        .pw-nav__link {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: .2rem;
            padding: .45rem .5rem;
            border-radius: 999px;
            color: inherit;
            text-decoration: none;
            transition: all .2s ease;
        }
        .pw-nav__icon {
            width: 17px;
            height: 17px;
            opacity: .85;
            transition: transform .2s ease;
        }
        .pw-nav__text {
            font-size: .62rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            white-space: nowrap;
            opacity: .8;
        }
        .pw-nav__link:hover,
        .pw-nav__link:focus-visible {
            background: var(--pw-accent, currentColor);
            color: var(--pw-accent-ink, #000);
        }
        .pw-nav__link:hover .pw-nav__icon {
            transform: scale(1.12);
            opacity: 1;
        }
        .pw-nav__link:hover .pw-nav__text {
            opacity: 1;
        }
        body:has(.pw-cover-switch:not(:checked)) .pw-nav { display: none; }
        body:has(.pw-cover-switch:not(:checked)) { padding-bottom: 0; }
        @media print {
            .pw-nav { display: none; }
            body { padding-bottom: 0; }
        }
    </style>
@endif
