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

    $date = null;

    if (! empty($primary['starts_at'])) {
        try {
            $date = \Illuminate\Support\Carbon::parse((string) $primary['starts_at']);
        } catch (\Throwable) {
            $date = null;
        }
    }

    $recipient = (isset($guest) && $guest !== null ? $guest->display_name : null) ?: request('to');

    $qrPayload = ($token ?? null) !== null
        ? route('guests.invitation.show', ['token' => $token])
        : url()->current();

    // Aesthetic prewedding imagery
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
<html lang="id" data-template="emerald-gilded.v1">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $invitation->coupleHeadline() ?: 'Undangan Pernikahan' }}</title>
    @include('invitations.partials.head-meta')
    <style>
        :root {
            --emerald-deep: #031812;
            --emerald: #06261d;
            --emerald-soft: #0c3b2e;
            --gold: #c8a96a;
            --gold-light: #f5eccf;
            --gold-dim: #a1844b;
            --cream: #fbf7ef;
            --cream-card: rgba(252, 249, 242, 0.96);
            --ink: #17261e;
            --ink-muted: #58675d;
            --serif: "Cormorant Garamond", "Playfair Display", Georgia, serif;
            --display: "Cinzel", "Cormorant Garamond", Georgia, serif;
            --script: "Great Vibes", "Alex Brush", cursive;
            --sans: "Plus Jakarta Sans", system-ui, sans-serif;

            /* Shared power kit */
            --pw-accent: #c8a96a;
            --pw-accent-ink: #031812;
            --pw-line: rgba(200, 169, 106, .35);
            --pw-surface: rgba(6, 38, 29, .82);
            --pw-radius: 20px;
            --pw-display: "Cormorant Garamond", Georgia, serif;
            --pw-numeric: "Plus Jakarta Sans", ui-sans-serif, sans-serif;
            --pw-nav-bg: rgba(3, 24, 18, .94);
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            background:
                radial-gradient(ellipse 90% 50% at 50% 0%, rgba(200, 169, 106, .2), transparent 70%),
                radial-gradient(circle at 10% 40%, rgba(12, 59, 46, .3), transparent 60%),
                radial-gradient(circle at 90% 70%, rgba(12, 59, 46, .3), transparent 60%),
                var(--emerald-deep);
            color: var(--cream);
            font-family: var(--serif);
            font-size: 1.05rem;
            line-height: 1.8;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }
        a { color: var(--gold-light); }

        /* Smartphone Canvas standard (480px) for soft and focused reading */
        .sheet {
            max-width: 480px;
            margin: 0 auto;
            padding: 0 18px 88px;
            position: relative;
        }

        /* ---- Cover Gate (Ethereal & Seamless) ---- */
        .cover-switch {
            position: absolute;
            top: 0;
            left: 0;
            width: 1px;
            height: 1px;
            margin: 0;
            opacity: 0;
            pointer-events: none;
        }
        .cover-switch:focus-visible ~ .cover .cover__open {
            outline: 2px solid var(--gold-light);
            outline-offset: 5px;
        }
        .cover {
            position: fixed;
            inset: 0;
            z-index: 60;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px 18px;
            overflow-y: auto;
            background:
                radial-gradient(circle at 50% 25%, rgba(200, 169, 106, .22), transparent 60%),
                radial-gradient(circle at 50% 75%, rgba(6, 38, 29, .6), transparent 70%),
                var(--emerald-deep);
            transition: opacity .8s cubic-bezier(0.16, 1, 0.3, 1), visibility .8s cubic-bezier(0.16, 1, 0.3, 1), transform .8s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .cover-switch:checked ~ .cover {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transform: scale(1.05);
        }
        body:has(.cover-switch:not(:checked)) { overflow: hidden; }

        .cover .card {
            margin: 0;
            width: min(100%, 25rem);
            padding: 3.5rem 2.2rem;
            border-radius: 32px;
            background: linear-gradient(165deg, rgba(254, 251, 245, 0.98) 0%, rgba(246, 240, 227, 0.96) 60%, rgba(238, 229, 210, 0.98) 100%);
            border: 1px solid rgba(200, 169, 106, 0.45);
            box-shadow: 0 28px 70px -15px rgba(0, 0, 0, 0.85), 0 0 45px rgba(200, 169, 106, 0.2);
            text-align: center;
            position: relative;
        }
        .cover .eyebrow {
            margin: 0;
            font-family: var(--sans);
            font-size: .62rem;
            letter-spacing: .42em;
            text-transform: uppercase;
            color: var(--gold-dim);
            font-weight: 500;
        }
        .cover .names {
            margin: .8rem 0 .4rem;
            font-family: var(--script);
            font-size: clamp(2.4rem, 10vw, 3.4rem);
            font-weight: 400;
            line-height: 1.15;
            color: var(--emerald-deep);
            text-shadow: 0 2px 10px rgba(200, 169, 106, 0.2);
        }
        .cover .date-badge {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            margin-top: .8rem;
            padding: .35rem 1.1rem;
            border-top: 1px solid rgba(200, 169, 106, .4);
            border-bottom: 1px solid rgba(200, 169, 106, .4);
            font-family: var(--sans);
            font-size: .74rem;
            letter-spacing: .2em;
            text-transform: uppercase;
            color: var(--gold-dim);
        }
        .cover__guest-box {
            margin: 1.8rem auto 0;
            padding: 1.1rem 1.4rem;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.75);
            border: 1px solid rgba(200, 169, 106, 0.35);
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.04);
        }
        .cover__guest-box .role {
            margin: 0;
            font-family: var(--sans);
            font-size: .6rem;
            letter-spacing: .25em;
            text-transform: uppercase;
            color: var(--ink-muted);
        }
        .cover__guest-box .name {
            margin: .4rem 0 0;
            font-family: var(--serif);
            font-size: 1.35rem;
            font-weight: 600;
            font-style: italic;
            color: var(--emerald-deep);
        }
        .cover__guest-box .subtext {
            margin: .35rem 0 0;
            font-family: var(--sans);
            font-size: .62rem;
            color: var(--ink-muted);
            opacity: .85;
        }
        .cover__open {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .6rem;
            margin-top: 2rem;
            padding: .85rem 2.8rem;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--emerald) 0%, var(--emerald-deep) 100%);
            border: 1px solid var(--gold);
            color: var(--gold-light);
            font-family: var(--sans);
            font-size: .72rem;
            font-weight: 600;
            letter-spacing: .28em;
            text-transform: uppercase;
            cursor: pointer;
            box-shadow: 0 10px 30px -5px rgba(3, 24, 18, 0.7), 0 0 25px rgba(200, 169, 106, 0.3);
            transition: all .3s cubic-bezier(0.16, 1, 0.3, 1);
            animation: soft-glow 3s infinite ease-in-out;
        }
        .cover__open:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 35px -5px rgba(3, 24, 18, 0.85), 0 0 35px rgba(200, 169, 106, 0.5);
        }
        @keyframes soft-glow {
            0%, 100% { box-shadow: 0 10px 30px -5px rgba(3, 24, 18, 0.7), 0 0 18px rgba(200, 169, 106, 0.2); transform: scale(1); }
            50% { box-shadow: 0 12px 35px -3px rgba(3, 24, 18, 0.85), 0 0 30px rgba(200, 169, 106, 0.45); transform: scale(1.02); }
        }

        /* ---- Content Cards (Soft & Seamless) ---- */
        .card {
            position: relative;
            margin: 3.2rem 0;
            padding: 3.4rem 2.2rem;
            background: var(--cream-card);
            color: var(--ink);
            border-radius: 28px;
            border: 1px solid rgba(200, 169, 106, 0.35);
            box-shadow: 0 24px 60px -20px rgba(0, 0, 0, 0.75), 0 0 40px rgba(200, 169, 106, 0.08);
            text-align: center;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        .card__body { position: relative; max-width: 25rem; margin: 0 auto; text-align: center; }

        .sec-eyebrow {
            margin: 0 0 .5rem;
            font-family: var(--sans);
            font-size: .64rem;
            letter-spacing: .38em;
            text-transform: uppercase;
            color: var(--gold-dim);
            font-weight: 500;
        }
        .sec-title {
            margin: 0 0 1.2rem;
            font-family: var(--serif);
            font-size: clamp(2rem, 8vw, 2.5rem);
            font-weight: 400;
            font-style: italic;
            color: var(--emerald-deep);
            line-height: 1.2;
        }
        .gold-divider {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin: 1.2rem auto 1.8rem;
            color: var(--gold);
            font-size: .75rem;
        }
        .gold-divider::before, .gold-divider::after {
            content: '';
            width: 45px;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(200, 169, 106, .6));
        }
        .gold-divider::after {
            background: linear-gradient(270deg, transparent, rgba(200, 169, 106, .6));
        }

        /* Hero Arch */
        .hero-arch {
            position: relative;
            width: min(100%, 280px);
            aspect-ratio: 4 / 5.2;
            margin: 1.8rem auto 1.6rem;
            border-radius: 160px 160px 24px 24px;
            overflow: hidden;
            border: 2px solid var(--gold);
            box-shadow: 0 25px 60px -15px rgba(3, 24, 18, 0.45), 0 0 35px rgba(200, 169, 106, 0.2);
        }
        .hero-arch img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform .8s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .hero-arch:hover img { transform: scale(1.05); }

        .quote-box {
            margin: 1.8rem auto 0;
            padding: 1.3rem 1.6rem;
            background: rgba(200, 169, 106, 0.08);
            border-left: 2px solid var(--gold);
            border-radius: 0 16px 16px 0;
            font-size: .95rem;
            font-style: italic;
            color: #3b4c40;
            line-height: 1.8;
        }
        .quote-box cite {
            display: block;
            margin-top: .6rem;
            font-style: normal;
            font-family: var(--sans);
            font-size: .68rem;
            letter-spacing: .2em;
            text-transform: uppercase;
            color: var(--gold-dim);
            font-weight: 500;
        }

        /* Mempelai Profiles (Soft & Arch) */
        .couple-container {
            display: flex;
            flex-direction: column;
            gap: 2.6rem;
            margin-top: 1.8rem;
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
            border: 2px solid var(--gold);
            box-shadow: 0 16px 36px -10px rgba(0, 0, 0, 0.2);
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
        .person-role {
            font-family: var(--sans);
            font-size: .64rem;
            letter-spacing: .25em;
            text-transform: uppercase;
            color: var(--gold-dim);
            font-weight: 600;
            margin: 0;
        }
        .person-name {
            margin: .4rem 0 0;
            font-family: var(--serif);
            font-size: 1.85rem;
            font-weight: 600;
            font-style: italic;
            color: var(--emerald-deep);
            line-height: 1.25;
        }
        .person-parents {
            margin: .5rem auto 0;
            max-width: 22rem;
            font-size: .9rem;
            color: var(--ink-muted);
            line-height: 1.65;
        }
        .person-bio {
            margin: .4rem auto 0;
            max-width: 22rem;
            font-size: .86rem;
            color: #4a5a4e;
            font-style: italic;
        }
        .ig-pill {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            margin-top: .9rem;
            padding: .35rem 1.1rem;
            border: 1px solid rgba(200, 169, 106, .5);
            border-radius: 999px;
            font-family: var(--sans);
            font-size: .74rem;
            color: var(--emerald-deep);
            text-decoration: none;
            background: rgba(255, 255, 255, 0.7);
            transition: all .25s ease;
        }
        .ig-pill:hover {
            background: var(--gold);
            color: #fff;
            border-color: var(--gold);
            transform: translateY(-2px);
        }
        .couple-amp {
            font-family: var(--script);
            font-size: 2.8rem;
            color: var(--gold);
            line-height: 1;
            margin: .2rem 0;
        }

        /* Event Cards */
        .event-card {
            margin-top: 1.6rem;
            padding: 1.8rem 1.6rem;
            border: 1px solid rgba(200, 169, 106, .35);
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.65);
            box-shadow: 0 8px 24px -10px rgba(0, 0, 0, 0.05);
            text-align: center;
        }
        .event-card:first-of-type { margin-top: .6rem; }
        .event-badge {
            display: inline-block;
            padding: .35rem 1.1rem;
            background: rgba(200, 169, 106, 0.16);
            border-radius: 999px;
            font-family: var(--sans);
            font-size: .65rem;
            letter-spacing: .2em;
            text-transform: uppercase;
            color: var(--gold-dim);
            font-weight: 600;
        }
        .event-title {
            margin: .7rem 0 .3rem;
            font-family: var(--serif);
            font-size: 1.75rem;
            font-weight: 600;
            font-style: italic;
            color: var(--emerald-deep);
        }
        .event-time {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            margin: .4rem 0 .7rem;
            font-family: var(--sans);
            font-size: .88rem;
            color: #435447;
        }
        .event-venue { margin: .5rem 0 0; font-size: 1.15rem; font-weight: 600; color: var(--emerald-deep); }
        .event-address { margin: .3rem auto 0; max-width: 21rem; font-size: .88rem; color: var(--ink-muted); line-height: 1.6; }
        .btn-map {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            margin-top: 1.2rem;
            padding: .65rem 1.6rem;
            border-radius: 999px;
            background: var(--emerald);
            color: var(--gold-light);
            font-family: var(--sans);
            font-size: .74rem;
            font-weight: 600;
            letter-spacing: .12em;
            text-decoration: none;
            transition: all .25s ease;
            box-shadow: 0 4px 14px rgba(3, 24, 18, .3);
        }
        .btn-map:hover {
            background: var(--emerald-soft);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(3, 24, 18, .45);
        }

        /* Story Timeline */
        .story-text {
            font-size: .98rem;
            line-height: 1.85;
            color: #3b4c40;
            text-align: center;
            margin: 0 auto;
            max-width: 24rem;
        }
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
            border-left: 1px solid rgba(200, 169, 106, .6);
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -5px;
            top: 6px;
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: var(--gold);
            box-shadow: 0 0 10px rgba(200, 169, 106, .8);
        }
        .timeline-year {
            font-family: var(--sans);
            font-size: .68rem;
            font-weight: 600;
            letter-spacing: .15em;
            color: var(--gold-dim);
            text-transform: uppercase;
        }
        .timeline-title {
            margin: .25rem 0 0;
            font-family: var(--serif);
            font-size: 1.25rem;
            font-weight: 600;
            font-style: italic;
            color: var(--emerald-deep);
        }
        .timeline-desc {
            margin: .3rem 0 0;
            font-size: .88rem;
            color: var(--ink-muted);
            line-height: 1.6;
        }

        /* Gallery Grid */
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
            border: 1px solid rgba(200, 169, 106, .35);
            box-shadow: 0 8px 24px rgba(0, 0, 0, .08);
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

        /* QR Card */
        .qr-card {
            width: min(100%, 14rem);
            margin: 1.4rem auto 0;
            padding: .9rem;
            background: #fff;
            border: 1px solid var(--gold);
            border-radius: 18px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .06);
        }
        .qr-card svg { display: block; width: 100%; height: auto; aspect-ratio: 1; }
        .qr-hint { margin: 1.1rem auto 0; max-width: 22rem; font-size: .86rem; color: var(--ink-muted); }

        /* Scroll Reveal */
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
            margin-bottom: 24px;
            padding: 12px 18px;
            background: linear-gradient(135deg, var(--emerald) 0%, var(--emerald-deep) 100%);
            color: var(--gold-light);
            border: 1px solid var(--gold);
            border-radius: 12px;
            font-family: var(--sans);
            font-size: .8rem;
            letter-spacing: .03em;
            text-align: center;
        }
        footer {
            margin-top: 54px;
            text-align: center;
            color: var(--gold);
            font-family: var(--sans);
            font-size: .72rem;
            letter-spacing: .35em;
            text-transform: uppercase;
        }

        @media (prefers-reduced-motion: reduce) {
            .cover, .cover__open, .hero-arch img, .gallery-item img, .reveal-on-scroll {
                transition: none !important;
                animation: none !important;
            }
        }
        @media print {
            .cover, .cover-switch { display: none !important; }
            body { background: #fff; color: #000; }
        }
    </style>
</head>
<body class="emerald">
    <main class="sheet">
        {{-- Cover Switch & Gate --}}
        <input class="cover-switch pw-cover-switch" type="checkbox" id="emerald-cover" aria-label="Tampilkan isi undangan">
        <div class="cover">
            <div class="card">
                <div class="card__body">
                    <p class="eyebrow">{{ $cover['title'] ?? 'The Wedding Of' }}</p>

                    <h1 class="names">
                        @forelse ($nicknames as $nickname)
                            <span>{{ $nickname }}</span>
                            @if (! $loop->last)
                                <span class="amp" aria-hidden="true" style="font-family: var(--script); font-weight: normal; font-size: .8em; color: var(--gold); display: inline-block; margin: 0 .2rem;">&amp;</span>
                            @endif
                        @empty
                            <span>{{ $invitation->coupleHeadline() ?: 'Mempelai' }}</span>
                        @endforelse
                    </h1>

                    @if ($date !== null)
                        <div class="date-badge">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                            <span>{{ $date->copy()->locale('id')->translatedFormat('d F Y') }}</span>
                        </div>
                    @endif

                    <div class="cover__guest-box">
                        <p class="role">Kepada Yth. Bapak/Ibu/Saudara/i</p>
                        <p class="name">{{ $recipient ?? 'Tamu Undangan' }}</p>
                        <p class="subtext">Mohon maaf apabila ada kesalahan penulisan nama/gelar</p>
                    </div>

                    <label class="cover__open" for="emerald-cover">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                        Buka Undangan
                    </label>
                </div>
            </div>
        </div>

        @include('invitations.partials.demo-bar')
        @include('invitations.partials.showcase-bar')
        @include('invitations.partials.personal-bar')
        @if ($preview ?? false)
            <div class="preview-banner">Pratinjau draf — halaman ini belum diterbitkan dan tidak dapat diakses tamu.</div>
        @endif

        {{-- 1. Hero / Pembuka Card --}}
        <section class="card reveal-on-scroll" aria-label="Pembuka">
            <div class="card__body">
                <p class="sec-eyebrow">{{ $cover['title'] ?? 'The Wedding Of' }}</p>
                <h2 class="sec-title">{{ $invitation->coupleHeadline() ?: 'Ayu & Bima' }}</h2>

                @include('invitations.partials.photo', ['src' => $heroImage, 'class' => 'hero-arch', 'alt' => 'Foto mempelai', 'initial' => $monogram])

                @if (! empty($cover['greeting']))
                    <p class="story-text">{{ $cover['greeting'] }}</p>
                @endif

                @if (! empty($cover['quote']))
                    <blockquote class="quote-box">
                        &ldquo;{{ $cover['quote'] }}&rdquo;
                        @if (! empty($cover['quote_source']))
                            <cite>{{ $cover['quote_source'] }}</cite>
                        @endif
                    </blockquote>
                @endif
            </div>
        </section>

        {{-- 2. Mempelai Profiles Section --}}
        @if ($couple !== [])
            <section class="card reveal-on-scroll" id="mempelai" aria-label="Mempelai">
                <div class="card__body">
                    <p class="sec-eyebrow">Pasangan Mempelai</p>
                    <h2 class="sec-title">Mempelai</h2>
                    <div class="gold-divider" aria-hidden="true">&#10047;</div>

                    <div class="couple-container">
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

                                <p class="person-role">
                                    {{ $person['type'] === 'groom' ? 'Mempelai Pria' : ($person['type'] === 'bride' ? 'Mempelai Wanita' : 'Mempelai') }}
                                </p>

                                <h3 class="person-name">{{ $person['display_name'] ?? '' }}</h3>

                                @if (! empty($person['parents']))
                                    <p class="person-parents">
                                        {{ $isBride ? 'Putri tercinta dari' : 'Putra tercinta dari' }} {{ $person['parents'] }}
                                    </p>
                                @endif

                                @if (! empty($person['biography']))
                                    <p class="person-bio">{{ $person['biography'] }}</p>
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
                </div>
            </section>
        @endif

        {{-- 3. Rangkaian Acara Section --}}
        @if (($features['show_events'] ?? true) && $events !== [])
            <section class="card reveal-on-scroll" id="acara" aria-label="Acara">
                <div class="card__body">
                    <p class="sec-eyebrow">Agenda Pernikahan</p>
                    <h2 class="sec-title">Rangkaian Acara</h2>
                    <div class="gold-divider" aria-hidden="true">&#10047;</div>

                    @foreach ($events as $event)
                        <article class="event-card">
                            <span class="event-badge">{{ $event['type'] === 'ceremony' ? 'Akad Nikah' : 'Resepsi' }}</span>
                            <h3 class="event-title">{{ $event['title'] ?? '' }}</h3>

                            @if (! empty($event['starts_label']))
                                <p class="event-time">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                    <span>
                                        {{ $event['starts_label'] }}
                                        @if (! empty($event['ends_label']))
                                            &ndash; {{ $event['ends_label'] }}
                                        @endif
                                    </span>
                                </p>
                            @endif

                            <p class="event-venue">{{ $event['venue_name'] ?? '' }}</p>

                            @if (! empty($event['address']))
                                <p class="event-address">{{ $event['address'] }}</p>
                            @endif

                            @if (($features['show_map'] ?? true) && ! empty($event['map_url']))
                                <a class="btn-map" href="{{ $event['map_url'] }}" target="_blank" rel="noopener noreferrer">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                        <circle cx="12" cy="10" r="3"></circle>
                                    </svg>
                                    Lihat peta &rarr;
                                </a>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- 4. Cerita Kami (Story) Section --}}
        @if (($features['show_story'] ?? true) && ! empty($story['body']))
            <section class="card reveal-on-scroll" aria-label="Cerita">
                <div class="card__body">
                    <p class="sec-eyebrow">Perjalanan Cinta</p>
                    <h2 class="sec-title">{{ $story['heading'] ?? 'Cerita Kami' }}</h2>
                    <div class="gold-divider" aria-hidden="true">&#10047;</div>

                    <p class="story-text">{!! nl2br(e($story['body'])) !!}</p>

                    <div class="story-timeline">
                        <div class="timeline-item">
                            <span class="timeline-year">2018</span>
                            <h4 class="timeline-title">Pertama Bertemu</h4>
                            <p class="timeline-desc">Pertemuan pertama di bangku kuliah yang menumbuhkan rasa saling mengagumi.</p>
                        </div>
                        <div class="timeline-item">
                            <span class="timeline-year">2021</span>
                            <h4 class="timeline-title">Menjalin Komitmen</h4>
                            <p class="timeline-desc">Memutuskan untuk saling mendampingi, bertumbuh dan belajar bersama.</p>
                        </div>
                        <div class="timeline-item">
                            <span class="timeline-year">2024</span>
                            <h4 class="timeline-title">Momen Lamaran</h4>
                            <p class="timeline-desc">Mengikat janji suci dan memohon restu di hadapan kedua keluarga besar.</p>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        {{-- 5. Galeri Prewedding — hanya muncul bila pasangan punya foto. --}}
        @if ($galleryImages !== [])
            <section class="card reveal-on-scroll" aria-label="Galeri">
                <div class="card__body">
                    <p class="sec-eyebrow">Momen Bahagia</p>
                    <h2 class="sec-title">Galeri Prewedding</h2>
                    <div class="gold-divider" aria-hidden="true">&#10047;</div>

                    @include('invitations.partials.gallery', ['galleryImages' => $galleryImages])
                </div>
            </section>
        @endif

        {{-- 6. Simpan Undangan / QR Code --}}
        <section class="card reveal-on-scroll" aria-label="Kode undangan">
            <div class="card__body">
                <p class="sec-eyebrow">Digital Access</p>
                <h2 class="sec-title">Simpan Undangan</h2>
                <div class="gold-divider" aria-hidden="true">&#10047;</div>

                <div class="qr-card">
                    {!! \App\Support\Invitations\QrCode::svg($qrPayload, '#06261d', '#ffffff', 'Kode QR undangan') !!}
                </div>

                <p class="qr-hint">Pindai kode di atas untuk membuka undangan ini kembali kapan saja atau simpan link ini di ponsel Anda.</p>
            </div>
        </section>

        {{-- Power Kit (Countdown, Hadiah ATM Card, Wishes, Music, Floating Nav) --}}
        @include('invitations.partials.power')

        @include('invitations.partials.extras')

        <footer>{{ $invitation->templateName }}</footer>
    </main>
</body>
</html>
