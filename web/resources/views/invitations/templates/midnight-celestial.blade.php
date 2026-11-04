@php
    $cover = $invitation->content['cover'] ?? [];
    $story = $invitation->content['story'] ?? null;
    $features = $invitation->content['features'] ?? [];
    $couple = $invitation->couple;
    $events = $invitation->events;
    $primary = $events[0] ?? [];

    $nicknames = array_values(array_filter(array_map(
        static fn (array $person): string => trim((string) ($person['nickname'] ?? $person['display_name'] ?? '')),
        $couple,
    )));

    $initials = array_values(array_filter(array_map(
        static fn (string $name): string => mb_strtoupper(mb_substr($name, 0, 1)),
        $nicknames,
    )));

    $date = null;

    if (! empty($primary['starts_at'])) {
        try {
            $date = \Illuminate\Support\Carbon::parse((string) $primary['starts_at']);
        } catch (\Throwable) {
            $date = null;
        }
    }

    $recipient = (isset($guest) && $guest !== null ? $guest->display_name : null) ?: request('to');

    // Default aesthetic prewedding imagery
    // Photos come from the couple's own public albums, or the demo set on a
    // sample page. A real invitation never borrows stock photography.
    $media ??= \App\Support\Invitations\InvitationMedia::for($invitation, $demo ?? false);
    $heroImage = $media->hero();
    $brideImage = $media->portrait('bride');
    $groomImage = $media->portrait('groom');
    $galleryImages = $media->gallery();
    $monogram = collect($invitation->couple)
        ->map(static fn (array $person): string => trim((string) ($person['nickname'] ?? $person['display_name'] ?? '')))
        ->filter()
        ->map(static fn (string $name): string => mb_strtoupper(mb_substr($name, 0, 1)))
        ->take(2)
        ->implode('·');
@endphp
<!DOCTYPE html>
<html lang="id" data-template="midnight-celestial.v1">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $invitation->coupleHeadline() ?: 'Undangan Pernikahan' }}</title>
    @include('invitations.partials.head-meta')
    <style>
        :root {
            --night: #060913;
            --night-card: rgba(13, 20, 38, 0.65);
            --night-glow: rgba(227, 201, 160, 0.12);
            --champagne: #e3c9a0;
            --champagne-light: #f9f2e3;
            --champagne-dim: #bba073;
            --star: #f5f2eb;
            --muted: #a1abc2;
            --serif: "Cormorant Garamond", Georgia, serif;
            --display: "Cinzel", "Cormorant Garamond", Georgia, serif;
            --script: "Great Vibes", "Alex Brush", cursive;
            --sans: "Plus Jakarta Sans", system-ui, sans-serif;

            /* Shared power kit */
            --pw-accent: #e3c9a0;
            --pw-accent-ink: #060913;
            --pw-line: rgba(227, 201, 160, .2);
            --pw-surface: rgba(14, 22, 42, .65);
            --pw-radius: 20px;
            --pw-display: "Cormorant Garamond", Georgia, serif;
            --pw-numeric: "Plus Jakarta Sans", ui-sans-serif, sans-serif;
            --pw-nav-bg: rgba(6, 9, 19, .92);
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            color: var(--star);
            font-family: var(--serif);
            font-size: 1.05rem;
            line-height: 1.8;
            background-color: var(--night);
            background-image:
                radial-gradient(ellipse 90% 60% at 50% 0%, rgba(227, 201, 160, .14), transparent 70%),
                radial-gradient(circle at 10% 40%, rgba(35, 48, 88, .3), transparent 60%),
                radial-gradient(circle at 90% 70%, rgba(35, 48, 88, .3), transparent 60%),
                radial-gradient(1.4px 1.4px at 28px 36px, rgba(255, 255, 255, .7), transparent 60%),
                radial-gradient(1px 1px at 120px 160px, rgba(255, 255, 255, .45), transparent 60%),
                radial-gradient(1.6px 1.6px at 210px 70px, rgba(227, 201, 160, .6), transparent 60%);
            background-size: cover, cover, cover, 260px 260px, 260px 260px, 260px 260px;
            background-repeat: no-repeat, no-repeat, no-repeat, repeat, repeat, repeat;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }
        a { color: var(--champagne); }

        /* Container: Smartphone Canvas Standard (Soft & Immersive) */
        .page {
            max-width: 480px;
            margin: 0 auto;
            padding: 0 20px 88px;
            position: relative;
        }

        /* Seamless Ambient Section Containers */
        .soft-section {
            position: relative;
            margin: 3.2rem 0;
            padding: 3.2rem 2rem;
            border-radius: 28px;
            background: linear-gradient(180deg, rgba(16, 24, 46, 0.55) 0%, rgba(10, 15, 30, 0.45) 100%);
            border: 1px solid rgba(227, 201, 160, 0.16);
            box-shadow: 0 24px 60px -20px rgba(0, 0, 0, 0.7), 0 0 40px rgba(227, 201, 160, 0.05);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            text-align: center;
        }

        .sec-kicker {
            margin: 0 0 .5rem;
            font-family: var(--sans);
            font-size: .64rem;
            letter-spacing: .38em;
            text-transform: uppercase;
            color: var(--champagne-dim);
            font-weight: 400;
        }
        .sec-title {
            margin: 0 0 1.2rem;
            font-family: var(--serif);
            font-size: clamp(2rem, 8vw, 2.5rem);
            font-weight: 300;
            font-style: italic;
            line-height: 1.2;
            letter-spacing: .02em;
            background: linear-gradient(135deg, #ffffff 0%, var(--champagne-light) 40%, var(--champagne) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .ethereal-divider {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin: 1.2rem auto 1.8rem;
            color: var(--champagne);
            font-size: .75rem;
            opacity: .8;
        }
        .ethereal-divider::before, .ethereal-divider::after {
            content: '';
            width: 50px;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(227, 201, 160, .5));
        }
        .ethereal-divider::after {
            background: linear-gradient(270deg, transparent, rgba(227, 201, 160, .5));
        }

        /* ---- Fullscreen Cover Gate (Dreamy & Soft) ---- */
        .pw-cover-switch { position: absolute; width: 1px; height: 1px; margin: 0; opacity: 0; pointer-events: none; }
        .pw-cover-switch:focus-visible ~ .cover .cover__open { outline: 2px solid var(--champagne); outline-offset: 6px; }
        .cover {
            position: fixed;
            inset: 0;
            z-index: 60;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px 20px;
            overflow-y: auto;
            background:
                radial-gradient(circle at 50% 25%, rgba(227, 201, 160, .2), transparent 55%),
                radial-gradient(circle at 50% 75%, rgba(30, 42, 75, .6), transparent 65%),
                var(--night);
            transition: opacity .8s cubic-bezier(0.16, 1, 0.3, 1), visibility .8s cubic-bezier(0.16, 1, 0.3, 1), transform .8s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .pw-cover-switch:checked ~ .cover {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transform: scale(1.05);
        }
        body:has(.pw-cover-switch:not(:checked)) { overflow: hidden; }

        .cover__inner {
            width: min(100%, 25rem);
            text-align: center;
            padding: 3.5rem 2.2rem;
            border-radius: 32px;
            background: linear-gradient(175deg, rgba(16, 24, 48, 0.75) 0%, rgba(8, 12, 24, 0.88) 100%);
            border: 1px solid rgba(227, 201, 160, 0.28);
            box-shadow: 0 30px 70px -15px rgba(0, 0, 0, 0.9), 0 0 50px rgba(227, 201, 160, 0.12);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            position: relative;
        }
        .moon { display: block; width: 56px; margin: 0 auto; color: var(--champagne); opacity: .9; }
        .cover__label {
            margin: 1.5rem 0 0;
            font-family: var(--sans);
            font-size: .62rem;
            letter-spacing: .45em;
            text-transform: uppercase;
            color: var(--champagne-dim);
        }
        .cover__names {
            margin: 1rem 0 0;
            font-family: var(--script);
            font-size: clamp(2.4rem, 10vw, 3.4rem);
            font-weight: 400;
            line-height: 1.15;
            color: var(--champagne-light);
            text-shadow: 0 4px 20px rgba(227, 201, 160, 0.3);
        }
        .cover__date {
            margin: 1rem 0 0;
            font-family: var(--sans);
            font-size: .74rem;
            letter-spacing: .32em;
            text-transform: uppercase;
            color: var(--champagne);
            opacity: .9;
        }
        .cover__line {
            width: 1px;
            height: 40px;
            margin: 1.5rem auto;
            background: linear-gradient(180deg, transparent, rgba(227, 201, 160, .75), transparent);
        }
        .cover__guest-card {
            padding: 1.1rem 1.4rem;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(227, 201, 160, 0.22);
            backdrop-filter: blur(10px);
        }
        .cover__to { margin: 0; font-family: var(--sans); font-size: .58rem; letter-spacing: .3em; text-transform: uppercase; color: var(--muted); }
        .cover__guest {
            margin: .4rem 0 0;
            font-family: var(--serif);
            font-size: 1.3rem;
            font-weight: 400;
            font-style: italic;
            color: var(--star);
        }
        .cover__open {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .6rem;
            margin-top: 2rem;
            padding: .85rem 2.8rem;
            border: 1px solid var(--champagne);
            border-radius: 999px;
            background: linear-gradient(135deg, rgba(227, 201, 160, 0.25) 0%, rgba(227, 201, 160, 0.08) 100%);
            color: var(--champagne-light);
            font-family: var(--sans);
            font-size: .72rem;
            font-weight: 500;
            letter-spacing: .28em;
            text-transform: uppercase;
            cursor: pointer;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.7), 0 0 25px rgba(227, 201, 160, 0.25);
            transition: all .3s cubic-bezier(0.16, 1, 0.3, 1);
            animation: soft-pulse 3s infinite ease-in-out;
        }
        .cover__open:hover {
            background: var(--champagne);
            color: var(--night);
            transform: translateY(-2px);
            box-shadow: 0 14px 35px -5px rgba(0, 0, 0, 0.8), 0 0 35px rgba(227, 201, 160, 0.5);
        }
        @keyframes soft-pulse {
            0%, 100% { box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.7), 0 0 20px rgba(227, 201, 160, 0.2); transform: scale(1); }
            50% { box-shadow: 0 12px 35px -3px rgba(0, 0, 0, 0.8), 0 0 32px rgba(227, 201, 160, 0.4); transform: scale(1.02); }
        }

        /* Lead Section (Opening Screen inside invitation) */
        .lead { padding: 50px 0 0; text-align: center; }
        .lead__mark {
            display: grid;
            place-items: center;
            width: 54px;
            height: 54px;
            margin: 0 auto 1.5rem;
            border: 1px solid rgba(227, 201, 160, .35);
            border-radius: 50%;
            font-family: var(--serif);
            font-size: .88rem;
            letter-spacing: .15em;
            color: var(--champagne);
            box-shadow: 0 0 20px rgba(227, 201, 160, .12);
        }
        .lead__names {
            margin: .6rem 0 0;
            font-family: var(--script);
            font-size: clamp(2.6rem, 11vw, 3.8rem);
            font-weight: 400;
            line-height: 1.15;
            color: var(--champagne-light);
            text-shadow: 0 4px 20px rgba(227, 201, 160, 0.25);
        }
        .hero-arch {
            position: relative;
            width: min(100%, 280px);
            aspect-ratio: 4 / 5.2;
            margin: 2rem auto 1.6rem;
            border-radius: 160px 160px 24px 24px;
            overflow: hidden;
            border: 2px solid rgba(227, 201, 160, .4);
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.85), 0 0 40px rgba(227, 201, 160, 0.18);
        }
        .hero-arch img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform .8s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .hero-arch:hover img { transform: scale(1.05); }

        .lead__date { margin: 1rem 0 0; font-family: var(--sans); font-size: .78rem; letter-spacing: .28em; text-transform: uppercase; color: var(--champagne); }
        .greeting { margin: 1.4rem auto 0; max-width: 25rem; font-size: .95rem; color: var(--muted); line-height: 1.85; }
        .quote {
            margin: 2.2rem auto 0;
            max-width: 25rem;
            padding: 1.4rem 1.6rem;
            border-left: 2px solid var(--champagne);
            background: rgba(227, 201, 160, .04);
            border-radius: 0 16px 16px 0;
            font-style: italic;
            font-size: .98rem;
            color: #dbe0ee;
            line-height: 1.8;
        }
        .quote cite {
            display: block;
            margin-top: .7rem;
            font-style: normal;
            font-family: var(--sans);
            font-size: .68rem;
            letter-spacing: .22em;
            text-transform: uppercase;
            color: var(--champagne-dim);
        }

        /* Mempelai Profiles (Soft & Romantic) */
        .couple-wrapper {
            display: flex;
            flex-direction: column;
            gap: 2.6rem;
            margin-top: 2rem;
        }
        .person-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        .person-arch {
            width: 175px;
            height: 240px;
            border-radius: 110px 110px 20px 20px;
            overflow: hidden;
            border: 2px solid rgba(227, 201, 160, .35);
            box-shadow: 0 16px 36px -10px rgba(0, 0, 0, 0.7);
            margin-bottom: 1.2rem;
        }
        .person-arch img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform .7s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .person-arch:hover img { transform: scale(1.06); }
        .person__role { margin: 0; font-family: var(--sans); font-size: .64rem; letter-spacing: .28em; text-transform: uppercase; color: var(--champagne-dim); }
        .person__name {
            margin: .4rem 0 0;
            font-family: var(--serif);
            font-size: 1.85rem;
            font-weight: 400;
            font-style: italic;
            color: var(--star);
            line-height: 1.25;
        }
        .person__meta { margin: .5rem auto 0; max-width: 22rem; font-size: .9rem; color: var(--muted); line-height: 1.7; }
        .ig-pill {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            margin-top: .9rem;
            padding: .35rem 1.1rem;
            border: 1px solid rgba(227, 201, 160, .3);
            border-radius: 999px;
            font-family: var(--sans);
            font-size: .74rem;
            color: var(--champagne);
            text-decoration: none;
            background: rgba(255, 255, 255, .03);
            transition: all .25s ease;
        }
        .ig-pill:hover {
            background: var(--champagne);
            color: var(--night);
            border-color: var(--champagne);
            transform: translateY(-2px);
        }
        .couple-amp {
            font-family: var(--script);
            font-size: 2.8rem;
            color: var(--champagne);
            line-height: 1;
            margin: .2rem 0;
            opacity: .9;
        }

        /* Acara Section (Soft Event Cards) */
        .event-card {
            padding: 1.8rem 1.6rem;
            border: 1px solid rgba(227, 201, 160, .2);
            border-radius: 20px;
            background: rgba(255, 255, 255, .02);
            margin-top: 1.6rem;
            text-align: center;
        }
        .event-card:first-of-type { margin-top: .6rem; }
        .event__day {
            display: inline-block;
            padding: .35rem 1.1rem;
            background: rgba(227, 201, 160, .1);
            border-radius: 999px;
            font-family: var(--sans);
            font-size: .65rem;
            letter-spacing: .22em;
            text-transform: uppercase;
            color: var(--champagne);
        }
        .event__title {
            margin: .7rem 0 .3rem;
            font-family: var(--serif);
            font-size: 1.75rem;
            font-weight: 400;
            font-style: italic;
            color: var(--star);
        }
        .event__when {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            margin: .4rem 0 .7rem;
            font-family: var(--sans);
            font-size: .88rem;
            color: var(--champagne-light);
        }
        .event__venue { margin: .5rem 0 0; font-size: 1.15rem; font-weight: 500; color: var(--star); }
        .event__address { margin: .3rem auto 0; max-width: 21rem; font-size: .88rem; color: var(--muted); line-height: 1.6; }
        .event__map {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            margin-top: 1.2rem;
            padding: .65rem 1.6rem;
            border-radius: 999px;
            background: linear-gradient(135deg, rgba(227, 201, 160, 0.25) 0%, rgba(227, 201, 160, 0.08) 100%);
            border: 1px solid var(--champagne);
            color: var(--champagne-light);
            font-family: var(--sans);
            font-size: .74rem;
            font-weight: 500;
            letter-spacing: .12em;
            text-decoration: none;
            transition: all .25s ease;
        }
        .event__map:hover {
            background: var(--champagne);
            color: var(--night);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(227, 201, 160, .3);
        }

        /* Story Timeline */
        .story-text { font-size: .98rem; color: #cbd2e4; line-height: 1.85; margin: 0 auto; max-width: 24rem; }
        .story-timeline {
            display: grid;
            gap: 1.4rem;
            margin: 2rem auto 0;
            text-align: left;
            max-width: 24rem;
        }
        .timeline-item {
            position: relative;
            padding-left: 2rem;
            border-left: 1px solid rgba(227, 201, 160, .35);
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -5px;
            top: 6px;
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: var(--champagne);
            box-shadow: 0 0 10px rgba(227, 201, 160, .8);
        }
        .timeline-year {
            font-family: var(--sans);
            font-size: .68rem;
            letter-spacing: .18em;
            color: var(--champagne);
            text-transform: uppercase;
        }
        .timeline-title {
            margin: .25rem 0 0;
            font-family: var(--serif);
            font-size: 1.25rem;
            font-style: italic;
            color: var(--star);
        }
        .timeline-desc {
            margin: .3rem 0 0;
            font-size: .88rem;
            color: var(--muted);
            line-height: 1.6;
        }

        /* Galeri Prewedding */
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: .8rem;
            margin-top: 1.6rem;
        }
        .gallery-item {
            position: relative;
            aspect-ratio: 1;
            border-radius: 18px;
            overflow: hidden;
            border: 1px solid rgba(227, 201, 160, .25);
            box-shadow: 0 10px 25px -8px rgba(0, 0, 0, .6);
        }
        .gallery-item:first-child {
            grid-column: span 2;
            aspect-ratio: 16 / 9.5;
        }
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform .7s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .gallery-item:hover img { transform: scale(1.08); }

        /* Smooth Scroll Reveal */
        .reveal-on-scroll {
            opacity: 0;
            transform: translateY(28px);
            transition: opacity .9s cubic-bezier(0.16, 1, 0.3, 1), transform .9s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .reveal-on-scroll.is-revealed {
            opacity: 1;
            transform: translateY(0);
        }

        .preview-banner {
            padding: 12px 18px;
            background: var(--champagne);
            color: var(--night);
            border-radius: 12px;
            font-family: var(--sans);
            font-size: .8rem;
            letter-spacing: .03em;
            text-align: center;
            margin-bottom: 24px;
        }
        footer { margin-top: 60px; text-align: center; color: var(--muted); }
        footer .moon { width: 34px; margin-bottom: 1rem; opacity: .8; }
        footer p { margin: 0; font-family: var(--sans); font-size: .68rem; letter-spacing: .35em; text-transform: uppercase; color: var(--champagne-dim); }

        @media (prefers-reduced-motion: reduce) {
            .cover, .cover__open, .hero-arch img, .gallery-item img, .reveal-on-scroll {
                transition: none !important;
                animation: none !important;
            }
        }
        @media print {
            .cover, .pw-cover-switch { display: none !important; }
            body { background: #fff; color: #000; }
        }
    </style>
</head>
<body>
    <main class="page">
        {{-- Cover Switch & Fullscreen Gate --}}
        <input class="pw-cover-switch" type="checkbox" id="celestial-cover" aria-label="Buka undangan">
        <div class="cover">
            <div class="cover__inner">
                @include('invitations.partials.celestial-moon', ['class' => 'moon'])

                <p class="cover__label">{{ $cover['title'] ?? 'The Wedding Of' }}</p>

                <h2 class="cover__names">
                    @forelse ($nicknames as $nickname)
                        <span>{{ $nickname }}</span>
                        @if (! $loop->last)
                            <span class="amp" aria-hidden="true" style="font-family: var(--serif); font-size: .7em; display: inline-block; margin: 0 .2rem; opacity: .8;">&amp;</span>
                        @endif
                    @empty
                        {{ $invitation->coupleHeadline() ?: 'Mempelai' }}
                    @endforelse
                </h2>

                @if ($date !== null)
                    <p class="cover__date">{{ $date->copy()->locale('id')->translatedFormat('d F Y') }}</p>
                @endif

                <div class="cover__line" aria-hidden="true"></div>

                <div class="cover__guest-card">
                    <p class="cover__to">Kepada Yth. Bapak/Ibu/Saudara/i</p>
                    <p class="cover__guest">{{ $recipient ?? 'Tamu Undangan' }}</p>
                </div>

                <label class="cover__open" for="celestial-cover">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                    Buka Undangan
                </label>
            </div>
        </div>

        @include('invitations.partials.demo-bar')
        @include('invitations.partials.showcase-bar')
        @include('invitations.partials.personal-bar')
        @if ($preview ?? false)
            <div class="preview-banner">Pratinjau draf — halaman ini belum diterbitkan dan tidak dapat diakses tamu.</div>
        @endif

        {{-- 1. Hero / Lead Section --}}
        <section class="lead reveal-on-scroll" aria-label="Pembuka">
            @if ($initials !== [])
                <p class="lead__mark" aria-hidden="true">{{ implode('·', $initials) }}</p>
            @endif

            <p class="sec-kicker">{{ $cover['title'] ?? 'The Wedding Of' }}</p>

            <h1 class="lead__names">
                @forelse ($nicknames as $nickname)
                    <span>{{ $nickname }}</span>
                    @if (! $loop->last)
                        <span class="amp" aria-hidden="true" style="font-family: var(--serif); font-size: .65em; display: inline-block; margin: 0 .2rem; opacity: .8;">&amp;</span>
                    @endif
                @empty
                    {{ $invitation->coupleHeadline() ?: 'Mempelai' }}
                @endforelse
            </h1>

            @include('invitations.partials.photo', ['src' => $heroImage, 'class' => 'hero-arch', 'alt' => 'Foto mempelai', 'initial' => $monogram])

            @if ($date !== null)
                <p class="lead__date">{{ $date->copy()->locale('id')->translatedFormat('l, d F Y') }}</p>
            @endif

            @if (! empty($cover['greeting']))
                <p class="greeting">{{ $cover['greeting'] }}</p>
            @endif

            @if (! empty($cover['quote']))
                <blockquote class="quote">
                    &ldquo;{{ $cover['quote'] }}&rdquo;
                    @if (! empty($cover['quote_source']))
                        <cite>{{ $cover['quote_source'] }}</cite>
                    @endif
                </blockquote>
            @endif
        </section>

        {{-- 2. Mempelai Profiles Section --}}
        @if ($couple !== [])
            <section class="soft-section reveal-on-scroll" id="mempelai" aria-label="Mempelai">
                <p class="sec-kicker">Pasangan Pengantin</p>
                <h2 class="sec-title">Mempelai</h2>
                <div class="ethereal-divider" aria-hidden="true">&#10022;</div>

                <div class="couple-wrapper">
                    @foreach ($couple as $index => $person)
                        @if ($index > 0)
                            <div class="couple-amp" aria-hidden="true">&amp;</div>
                        @endif

                        @php
                            $isBride = ($person['type'] ?? '') === 'bride';
                            $photo = $isBride ? $brideImage : $groomImage;
                            $igHandle = $isBride ? 'ayulestari' : 'bimapratama';
                        @endphp

                        <article class="person-card">
                            @include('invitations.partials.photo', ['src' => $photo, 'class' => 'person-arch', 'alt' => $person['display_name'] ?? 'Mempelai', 'initial' => $monogram, 'size' => ['width' => 300, 'height' => 400]])

                            <p class="person__role">
                                {{ $person['type'] === 'groom' ? 'Mempelai Pria' : ($person['type'] === 'bride' ? 'Mempelai Wanita' : 'Mempelai') }}
                            </p>

                            <h3 class="person__name">{{ $person['display_name'] ?? '' }}</h3>

                            @if (! empty($person['parents']))
                                <p class="person__meta">
                                    {{ $isBride ? 'Putri tercinta dari' : 'Putra tercinta dari' }} {{ $person['parents'] }}
                                </p>
                            @endif

                            @if (! empty($person['biography']))
                                <p class="person__meta" style="font-style: italic;">{{ $person['biography'] }}</p>
                            @endif

                            <a href="https://instagram.com" target="_blank" rel="noopener noreferrer" class="ig-pill">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                                    <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                                    <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                                </svg>
                                <span>&#64;{{ $person['instagram'] ?? $igHandle }}</span>
                            </a>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- 3. Rangkaian Acara Section --}}
        @if (($features['show_events'] ?? true) && $events !== [])
            <section class="soft-section reveal-on-scroll" id="acara" aria-label="Acara">
                <p class="sec-kicker">Agenda Pernikahan</p>
                <h2 class="sec-title">Rangkaian Acara</h2>
                <div class="ethereal-divider" aria-hidden="true">&#10022;</div>

                @foreach ($events as $event)
                    <article class="event-card">
                        @if (! empty($event['starts_label']))
                            <span class="event__day">{{ $event['type'] === 'ceremony' ? 'Akad Nikah' : 'Resepsi' }}</span>
                        @endif

                        <h3 class="event__title">{{ $event['title'] ?? '' }}</h3>

                        @if (! empty($event['ends_label']))
                            <p class="event__when">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <span>{{ $event['starts_label'] ?? '' }} &ndash; {{ $event['ends_label'] }}</span>
                            </p>
                        @endif

                        <p class="event__venue">{{ $event['venue_name'] ?? '' }}</p>

                        @if (! empty($event['address']))
                            <p class="event__address">{{ $event['address'] }}</p>
                        @endif

                        @if (($features['show_map'] ?? true) && ! empty($event['map_url']))
                            <a class="event__map" href="{{ $event['map_url'] }}" target="_blank" rel="noopener noreferrer">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                                Lihat lokasi
                            </a>
                        @endif
                    </article>
                @endforeach
            </section>
        @endif

        {{-- 4. Cerita Kami (Story) Section --}}
        @if (($features['show_story'] ?? true) && ! empty($story['body']))
            <section class="soft-section story reveal-on-scroll" aria-label="Cerita">
                <p class="sec-kicker">Perjalanan Kasih</p>
                <h2 class="sec-title">Kisah Cinta</h2>
                <div class="ethereal-divider" aria-hidden="true">&#10022;</div>
                <p class="story-text">{!! nl2br(e($story['body'])) !!}</p>

                <div class="story-timeline">
                    <div class="timeline-item">
                        <span class="timeline-year">2018</span>
                        <h4 class="timeline-title">Pertama Bertemu</h4>
                        <p class="timeline-desc">Sebuah kebetulan indah di bangku kuliah yang mengawali segalanya.</p>
                    </div>
                    <div class="timeline-item">
                        <span class="timeline-year">2021</span>
                        <h4 class="timeline-title">Menjalin Hubungan</h4>
                        <p class="timeline-desc">Memilih untuk saling melengkapi, saling percaya, dan melangkah bersama.</p>
                    </div>
                    <div class="timeline-item">
                        <span class="timeline-year">2024</span>
                        <h4 class="timeline-title">Lamaran</h4>
                        <p class="timeline-desc">Mengikrarkan niat tulus untuk membangun keluarga sakinah mawaddah warahmah.</p>
                    </div>
                </div>
            </section>
        @endif

        {{-- 5. Galeri Foto Prewedding — hanya bila ada foto sungguhan. --}}
        @if ($galleryImages !== [])
            <section class="soft-section reveal-on-scroll" aria-label="Galeri">
                <p class="sec-kicker">Dokumentasi</p>
                <h2 class="sec-title">Momen Bahagia</h2>
                <div class="ethereal-divider" aria-hidden="true">&#10022;</div>

                @include('invitations.partials.gallery', ['galleryImages' => $galleryImages])
            </section>
        @endif

        {{-- Power Kit (Countdown, Hadiah ATM Card, Wishes, Music, Floating Nav) --}}
        @include('invitations.partials.power')

        @include('invitations.partials.extras')

        <footer>
            @include('invitations.partials.celestial-moon', ['class' => 'moon'])
            <p>{{ $invitation->templateName }}</p>
        </footer>
    </main>
</body>
</html>
