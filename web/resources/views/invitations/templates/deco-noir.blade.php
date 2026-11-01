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

    $initial = '';
    if ($nicknames !== []) {
        $initial = mb_strtoupper(mb_substr($nicknames[0], 0, 1))
            .'·'.(count($nicknames) > 1 ? mb_strtoupper(mb_substr($nicknames[1], 0, 1)) : '');
    }

    $date = null;

    if (! empty($primary['starts_at'])) {
        try {
            $date = \Illuminate\Support\Carbon::parse((string) $primary['starts_at']);
        } catch (\Throwable) {
            $date = null;
        }
    }

    $recipient = (isset($guest) && $guest !== null ? $guest->display_name : null) ?: request('to');

    $heroImage = asset('images/demo/couple-hero.webp');
    $brideImage = asset('images/demo/bride.webp');
    $groomImage = asset('images/demo/groom.webp');
    $galleryImages = [
        asset('images/demo/gallery-1.webp'),
        asset('images/demo/gallery-2.webp'),
        asset('images/demo/gallery-3.webp'),
        asset('images/demo/gallery-4.webp'),
    ];
@endphp
<!DOCTYPE html>
<html lang="id" data-template="deco-noir.v1">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $invitation->coupleHeadline() ?: 'Undangan Pernikahan' }}</title>
    @include('invitations.partials.head-meta')
    <style>
        :root {
            --noir: #111114;
            --noir-soft: #1b1b20;
            --champagne: #cbb27a;
            --champagne-pale: #e8dcc0;
            --ink: #f2ead6;
            --muted: #a79f8d;
            --line: rgba(203, 178, 122, .42);
            --line-soft: rgba(203, 178, 122, .18);
            --display: "Futura", "Century Gothic", "Avenir Next", "Trebuchet MS", ui-sans-serif, system-ui, sans-serif;
            --body: "Didot", "Bodoni MT", "Times New Roman", Georgia, serif;
            --sans: ui-sans-serif, system-ui, "Segoe UI", Helvetica, Arial, sans-serif;
            --pw-accent: #cbb27a;
            --pw-accent-ink: #111114;
            --pw-line: rgba(203, 178, 122, .5);
            --pw-surface: rgba(27, 27, 32, .7);
            --pw-radius: 0px;
            --pw-display: "Futura", "Century Gothic", "Avenir Next", ui-sans-serif, system-ui, sans-serif;
            --pw-numeric: ui-monospace, "SFM Mono", "Segoe UI", Consolas, monospace;
            --pw-nav-bg: rgba(17, 17, 20, .94);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: var(--ink);
            font-family: var(--body);
            line-height: 1.76;
            background:
                radial-gradient(90% 55% at 50% 0%, rgba(203, 178, 122, .1), rgba(203, 178, 122, 0) 70%),
                linear-gradient(180deg, var(--noir-soft) 0%, var(--noir) 60%, #0b0b0e 100%);
            background-attachment: fixed;
            -webkit-font-smoothing: antialiased;
        }
        a { color: var(--champagne); }

        /* A rayed fan is the whole ornament language: it closes a cover and
           separates a section without any picture. */
        .fan { display: block; margin: 0 auto; color: var(--champagne); }
        .chevron {
            height: 10px;
            background: repeating-linear-gradient(135deg, var(--line) 0 1px, transparent 1px 9px);
            opacity: .75;
        }
        .stack {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .55rem;
            margin: 1.15rem auto;
        }
        .stack span { width: 34px; height: 1px; background: var(--line); }
        .stack em {
            width: 6px;
            height: 6px;
            background: var(--champagne);
            transform: rotate(45deg);
        }

        /* ---- Cover gate: a lacquered box with a fan inlay. ---- */
        .pw-cover-switch { position: absolute; width: 1px; height: 1px; margin: 0; opacity: 0; pointer-events: none; }
        .pw-cover-switch:focus-visible ~ .cover .cover__open { outline: 2px solid var(--champagne); outline-offset: 6px; }
        .cover {
            position: fixed;
            inset: 0;
            z-index: 60;
            display: grid;
            place-items: center;
            padding: 20px;
            overflow-y: auto;
            background: linear-gradient(180deg, #1c1c21 0%, var(--noir) 55%, #08080a 100%);
            transition: opacity .5s ease, visibility .5s ease;
        }
        .pw-cover-switch:checked ~ .cover { opacity: 0; visibility: hidden; pointer-events: none; }
        body:has(.pw-cover-switch:not(:checked)) { overflow: hidden; }

        .cover__inner {
            position: relative;
            width: min(100%, 24rem);
            padding: 2.9rem 1.6rem 2.5rem;
            border: 1px solid var(--line);
            background: rgba(27, 27, 32, .72);
            text-align: center;
        }
        .cover__inner::before,
        .cover__inner::after {
            content: "";
            position: absolute;
            left: 14px;
            right: 14px;
            height: 10px;
            background: repeating-linear-gradient(135deg, var(--line) 0 1px, transparent 1px 8px);
        }
        .cover__inner::before { top: 8px; }
        .cover__inner::after { bottom: 8px; }
        .cover__fan { margin: 0 auto 1.5rem; width: 116px; }
        .cover__label {
            margin: 0;
            font-family: var(--display);
            font-size: .56rem;
            letter-spacing: .5em;
            text-transform: uppercase;
            color: var(--muted);
        }
        .cover__names {
            margin: 1.1rem 0 0;
            font-family: var(--display);
            font-size: clamp(1.25rem, 5.6vw, 1.75rem);
            font-weight: 400;
            line-height: 1.5;
            letter-spacing: .22em;
            text-transform: uppercase;
        }
        .cover__names .amp {
            display: block;
            margin: .1rem 0;
            font-family: var(--body);
            font-size: .8rem;
            letter-spacing: 0;
            text-transform: none;
            color: var(--champagne);
        }
        .cover__date {
            margin: 1.2rem 0 0;
            font-family: var(--display);
            font-size: .58rem;
            letter-spacing: .34em;
            text-transform: uppercase;
            color: var(--champagne);
        }
        .cover__to {
            margin: 1.7rem 0 0;
            font-family: var(--display);
            font-size: .52rem;
            letter-spacing: .3em;
            text-transform: uppercase;
            color: var(--muted);
        }
        .cover__guest { margin: .5rem 0 0; font-size: 1.05rem; letter-spacing: .04em; }
        .cover__open {
            display: inline-block;
            margin-top: 1.8rem;
            padding: .78rem 2rem;
            border: 1px solid var(--champagne);
            background: var(--champagne);
            color: var(--noir);
            font-family: var(--display);
            font-size: .6rem;
            font-weight: 600;
            letter-spacing: .3em;
            text-transform: uppercase;
            cursor: pointer;
        }
        .cover__open:hover { background: var(--champagne-pale); border-color: var(--champagne-pale); }

        /* ---- Page ---- */
        .preview-banner {
            padding: 10px 16px;
            background: var(--champagne);
            color: var(--noir);
            font-family: var(--sans);
            font-size: 13px;
            text-align: center;
        }
        .page { max-width: 570px; margin: 0 auto; padding: 0 22px 70px; }

        .lead {
            margin-top: 46px;
            padding: 2.7rem 1.5rem 2.2rem;
            border: 1px solid var(--line);
            background: rgba(27, 27, 32, .55);
            text-align: center;
        }
        .lead__eyebrow {
            margin: 0;
            font-family: var(--display);
            font-size: .55rem;
            letter-spacing: .5em;
            text-transform: uppercase;
            color: var(--muted);
        }
        h1.names {
            margin: 1.2rem 0 0;
            font-family: var(--display);
            font-size: clamp(1.5rem, 6.8vw, 2.3rem);
            font-weight: 400;
            line-height: 1.42;
            letter-spacing: .2em;
            text-transform: uppercase;
        }
        h1.names .amp {
            display: block;
            margin: .1rem 0;
            font-family: var(--body);
            font-size: .95rem;
            letter-spacing: 0;
            text-transform: none;
            color: var(--champagne);
        }
        .lead__date {
            margin: 1.1rem 0 0;
            font-family: var(--display);
            font-size: .58rem;
            letter-spacing: .32em;
            text-transform: uppercase;
            color: var(--champagne);
        }
        .greeting { margin: 1.7rem auto 0; max-width: 30rem; font-size: .95rem; color: var(--muted); }
        .quote {
            max-width: 29rem;
            margin: 1.5rem auto 0;
            font-size: .93rem;
            font-style: italic;
            color: var(--ink);
        }
        .quote cite {
            display: block;
            margin-top: .55rem;
            font-family: var(--display);
            font-style: normal;
            font-size: .54rem;
            letter-spacing: .28em;
            text-transform: uppercase;
            color: var(--champagne);
        }

        .block { margin-top: 3.1rem; }
        .block__head { text-align: center; margin-bottom: 1.5rem; }
        .block__index {
            font-family: var(--display);
            font-size: .53rem;
            letter-spacing: .36em;
            text-transform: uppercase;
            color: var(--champagne);
        }
        .block__title {
            margin: .5rem 0 0;
            font-family: var(--display);
            font-size: 1.3rem;
            font-weight: 400;
            letter-spacing: .3em;
            text-transform: uppercase;
        }
        .panel {
            padding: 1.8rem 1.4rem;
            border: 1px solid var(--line-soft);
            background: rgba(27, 27, 32, .5);
        }
        .panel + .panel { margin-top: 1rem; }
        .person { text-align: center; }
        .person__role {
            margin: 0;
            font-family: var(--display);
            font-size: .52rem;
            letter-spacing: .34em;
            text-transform: uppercase;
            color: var(--champagne);
        }
        .person__name { margin: .7rem 0 0; font-size: 1.38rem; letter-spacing: .1em; }
        .person__parents { margin: .55rem auto 0; max-width: 23rem; font-size: .85rem; color: var(--muted); }
        .person__bio { margin: .55rem auto 0; max-width: 23rem; font-size: .84rem; color: var(--muted); font-style: italic; }
        .story__heading { margin: 0 0 .7rem; font-family: var(--display); font-size: 1rem; letter-spacing: .2em; text-transform: uppercase; text-align: center; }
        .story__text { margin: 0; font-size: .93rem; color: #ded6c4; }
        .event { text-align: center; }
        .event__day {
            margin: 0;
            font-family: var(--display);
            font-size: .52rem;
            letter-spacing: .32em;
            text-transform: uppercase;
            color: var(--champagne);
        }
        .event__title { margin: .7rem 0 0; font-family: var(--display); font-size: 1.2rem; font-weight: 400; letter-spacing: .26em; text-transform: uppercase; }
        .event__when { margin: .5rem 0 0; font-family: var(--sans); font-size: .8rem; color: var(--muted); }
        .event__venue { margin: .9rem 0 0; font-size: 1.02rem; letter-spacing: .06em; }
        .event__address { margin: .15rem 0 0; font-size: .85rem; color: var(--muted); }
        .event__map {
            display: inline-block;
            margin-top: 1rem;
            padding: .62rem 1.5rem;
            border: 1px solid var(--champagne);
            font-family: var(--display);
            font-size: .56rem;
            letter-spacing: .26em;
            text-transform: uppercase;
            text-decoration: none;
        }
        .event__map:hover { background: var(--champagne); color: var(--noir); }

        footer {
            margin-top: 3.5rem;
            padding-top: 1.4rem;
            border-top: 1px solid var(--line-soft);
            text-align: center;
            font-family: var(--display);
            font-size: .54rem;
            letter-spacing: .3em;
            text-transform: uppercase;
            color: var(--muted);
        }
        footer p { margin: 0 0 .35rem; }

        @media (min-width: 640px) {
            .couple-panels { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
            .page { padding-left: 26px; padding-right: 26px; }
        }
        @media (max-width: 420px) {
            .page { padding: 0 16px 62px; }
            .cover__inner, .lead { padding: 2.1rem 1.1rem 1.8rem; }
            .panel { padding: 1.4rem 1.05rem; }
            .person__name, .event__venue, .person__parents { overflow-wrap: anywhere; }
            h1.names, .cover__names { letter-spacing: .14em; }
        }
        @media (prefers-reduced-motion: reduce) {
            .cover { transition: none; }
        }
        @media print {
            .cover, .pw-cover-switch { display: none !important; }
            body { background: #fff; color: #17181a; }
        }

        /* Visual Enhancements */
        .hero-arch {
            position: relative;
            width: min(100%, 250px);
            aspect-ratio: 4 / 5;
            margin: 1.5rem auto 1.3rem;
            border-radius: 125px 125px 14px 14px;
            overflow: hidden;
            border: 2px solid var(--pw-accent, currentColor);
            box-shadow: 0 14px 34px -8px rgba(0, 0, 0, .45);
        }
        .hero-arch img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform .6s ease;
        }
        .hero-arch:hover img { transform: scale(1.06); }
        .person-arch {
            width: 155px;
            height: 215px;
            border-radius: 85px 85px 12px 12px;
            overflow: hidden;
            border: 2px solid var(--pw-accent, currentColor);
            box-shadow: 0 10px 24px -6px rgba(0, 0, 0, .35);
            margin: 0 auto 1rem;
        }
        .person-arch img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform .5s ease;
        }
        .person-arch:hover img { transform: scale(1.06); }
        .cover__guest-card {
            padding: 1rem 1.2rem;
            border-radius: 12px;
            background: rgba(255, 255, 255, .07);
            border: 1px solid var(--pw-line, rgba(0, 0, 0, .2));
            margin-top: 1.2rem;
        }
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: .75rem;
            margin-top: 1.3rem;
        }
        .gallery-item {
            position: relative;
            aspect-ratio: 1;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--pw-line, rgba(0, 0, 0, .15));
            box-shadow: 0 6px 18px rgba(0, 0, 0, .25);
        }
        .gallery-item:first-child {
            grid-column: span 2;
            aspect-ratio: 16 / 9;
        }
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform .6s ease;
        }
        .gallery-item:hover img { transform: scale(1.08); }
        .reveal-on-scroll {
            opacity: 0;
            transform: translateY(26px);
            transition: opacity .8s cubic-bezier(0.16, 1, 0.3, 1), transform .8s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .reveal-on-scroll.is-revealed {
            opacity: 1;
            transform: translateY(0);
        }
        .cover__open {
            animation: pulse-open 2.5s infinite;
        }
        @keyframes pulse-open {
            0%, 100% { box-shadow: 0 6px 18px -4px rgba(0, 0, 0, .4), 0 0 10px var(--pw-line, transparent); }
            50% { box-shadow: 0 8px 24px -2px rgba(0, 0, 0, .6), 0 0 20px var(--pw-accent, transparent); }
        }
    </style>
</head>
<body>
    <main class="page">
        <input class="pw-cover-switch" type="checkbox" id="deco-cover" aria-label="Buka undangan">
        <div class="cover">
            <div class="cover__inner">
                {{-- Deco sunburst: rays fanning from a stepped base. --}}
                <svg class="fan cover__fan" viewBox="0 0 120 62" fill="none" stroke="currentColor" stroke-width="1"
                     stroke-linecap="round" aria-hidden="true" focusable="false">
                    <path d="M60 58V10"/>
                    <path d="M60 58 32 16M60 58l28-42" opacity=".85"/>
                    <path d="M60 58 14 30M60 58l46-28" opacity=".65"/>
                    <path d="M60 58 4 48M60 58l56-10" opacity=".45"/>
                    <path d="M40 58h40M28 58h64" opacity=".9"/>
                    <circle cx="60" cy="58" r="2.5" fill="currentColor" stroke="none"/>
                </svg>

                <p class="cover__label">{{ $cover['title'] ?? 'Undangan Pernikahan' }}</p>

                <h2 class="cover__names">
                    @forelse ($nicknames as $nickname)
                        <span>{{ $nickname }}</span>
                        @if (! $loop->last)
                            <span class="amp" aria-hidden="true">&amp;</span>
                        @endif
                    @empty
                        {{ $invitation->coupleHeadline() ?: 'Mempelai' }}
                    @endforelse
                </h2>

                <div class="stack" aria-hidden="true"><span></span><em></em><span></span></div>

                @if ($date !== null)
                    <p class="cover__date">{{ $date->copy()->locale('id')->translatedFormat('d F Y') }}</p>
                @endif

                <p class="cover__to">Kepada Yth.</p>
                <p class="cover__guest">{{ $recipient ?? 'Bapak/Ibu/Saudara/i' }}</p>

                <label class="cover__open" for="deco-cover">Buka Undangan</label>
            </div>
        </div>

        @include('invitations.partials.demo-bar')
        @include('invitations.partials.showcase-bar')
        @include('invitations.partials.personal-bar')
        @if ($preview ?? false)
            <div class="preview-banner">Pratinjau draf — halaman ini belum diterbitkan dan tidak dapat diakses tamu.</div>
        @endif

        <div class="hero-arch"><img src="{{ $heroImage }}" alt="Foto Mempelai" width="400" height="500"></div>
        <section class="lead" aria-label="Pembuka">
            <p class="lead__eyebrow">{{ $cover['title'] ?? 'The Wedding Of' }}</p>

            <h1 class="names">
                @forelse ($nicknames as $nickname)
                    <span>{{ $nickname }}</span>
                    @if (! $loop->last)
                        <span class="amp" aria-hidden="true">&amp;</span>
                    @endif
                @empty
                    {{ $invitation->coupleHeadline() ?: 'Mempelai' }}
                @endforelse
            </h1>

            <div class="stack" aria-hidden="true"><span></span><em></em><span></span></div>

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

        @if ($couple !== [])
            <section class="block" id="mempelai" aria-label="Mempelai">
                <div class="block__head">
                    <p class="block__index">01</p>
                    <h2 class="block__title">Mempelai</h2>
                    <div class="stack" aria-hidden="true"><span></span><em></em><span></span></div>
                </div>

                <div class="couple-panels">
                    @foreach ($couple as $person)
                        <div class="panel">
                            <div class="person">
                                @php
                            $isBride = ($person['type'] ?? '') === 'bride';
                            $photo = $isBride ? $brideImage : $groomImage;
                        @endphp
                        <div class="person-arch">
                            <img src="{{ $photo }}" alt="{{ $person['display_name'] ?? 'Mempelai' }}" width="300" height="400">
                        </div>
                        <p class="person__role">{{ $person['type'] === 'groom' ? 'Mempelai Pria' : ($person['type'] === 'bride' ? 'Mempelai Wanita' : 'Mempelai') }}</p>
                                <p class="person__name">{{ $person['display_name'] ?? '' }}</p>
                                @if (! empty($person['parents']))
                                    <p class="person__parents">Putra/Putri dari {{ $person['parents'] }}</p>
                                @endif
                                @if (! empty($person['biography']))
                                    <p class="person__bio">{{ $person['biography'] }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if (($features['show_story'] ?? true) && ! empty($story['body']))
            <section class="block" aria-label="Cerita">
                <div class="block__head">
                    <p class="block__index">02</p>
                    <h2 class="block__title">Cerita</h2>
                </div>

                <div class="panel">
                    @if (! empty($story['heading']))
                        <p class="story__heading">{{ $story['heading'] }}</p>
                    @endif
                    <p class="story__text">{!! nl2br(e($story['body'])) !!}</p>
                </div>
            </section>
        @endif

        @if (($features['show_events'] ?? true) && $events !== [])
            <section class="block" id="acara" aria-label="Acara">
                <div class="block__head">
                    <p class="block__index">03</p>
                    <h2 class="block__title">Acara</h2>
                    <div class="chevron" aria-hidden="true"></div>
                </div>

                @foreach ($events as $event)
                    <article class="panel event">
                        @if (! empty($event['starts_label']))
                            <p class="event__day">{{ $event['starts_label'] }}</p>
                        @endif
                        <h3 class="event__title">{{ $event['title'] ?? '' }}</h3>
                        @if (! empty($event['ends_label']))
                            <p class="event__when">Selesai sekitar pukul {{ $event['ends_label'] }}</p>
                        @endif
                        <p class="event__venue">{{ $event['venue_name'] ?? '' }}</p>
                        @if (! empty($event['address']))
                            <p class="event__address">{{ $event['address'] }}</p>
                        @endif
                        @if (($features['show_map'] ?? true) && ! empty($event['map_url']))
                            <a class="event__map" href="{{ $event['map_url'] }}" target="_blank" rel="noopener noreferrer">Lihat lokasi</a>
                        @endif
                    </article>
                @endforeach
            </section>
        @endif

        
        {{-- Galeri Foto Prewedding --}}
        <section class="reveal-on-scroll" aria-label="Galeri" style="margin-top: 2.4rem; text-align: center;">
            <p style="margin: 0; font-size: .65rem; letter-spacing: .35em; text-transform: uppercase; opacity: .75;">Dokumentasi</p>
            <h2 style="margin: .4rem 0 1rem; font-size: 1.6rem; font-weight: 600;">Momen Bahagia</h2>
            <div class="gallery-grid">
                @foreach ($galleryImages as $index => $gImg)
                    <div class="gallery-item">
                        <img src="{{ $gImg }}" alt="Galeri prewedding {{ $index + 1 }}" loading="lazy" width="600" height="400">
                    </div>
                @endforeach
            </div>
        </section>

        @include('invitations.partials.power')

        @include('invitations.partials.extras')

        <footer>
            <p>{{ $invitation->templateName }}</p>
            @if ($initial !== '')
                <p aria-hidden="true">{{ $initial }}</p>
            @endif
        </footer>
    </main>
</body>
</html>
