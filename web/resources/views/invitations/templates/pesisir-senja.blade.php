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
<html lang="id" data-template="pesisir-senja.v1">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $invitation->coupleHeadline() ?: 'Undangan Pernikahan' }}</title>
    @include('invitations.partials.head-meta')
    <style>
        :root {
            --sand: #f4efe6;
            --shell: #fdfaf4;
            --ink: #123033;
            --muted: #5c7275;
            --tide: #2c6f74;
            --sunset: #e0a06a;
            --line: rgba(18, 48, 51, .16);
            --line-soft: rgba(18, 48, 51, .08);
            --serif: "Palatino Linotype", Palatino, "Book Antiqua", Georgia, serif;
            --sans: ui-sans-serif, system-ui, "Segoe UI", Helvetica, Arial, sans-serif;
            --pw-accent: #2c6f74;
            --pw-accent-ink: #f6fbfa;
            --pw-line: rgba(18, 48, 51, .22);
            --pw-surface: rgba(253, 250, 244, .78);
            --pw-radius: 16px;
            --pw-display: "Palatino Linotype", Palatino, Georgia, serif;
            --pw-numeric: ui-sans-serif, system-ui, "Segoe UI", sans-serif;
            --pw-nav-bg: rgba(253, 250, 244, .92);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: var(--ink);
            font-family: var(--serif);
            line-height: 1.75;
            background: linear-gradient(180deg, var(--shell) 0%, var(--sand) 55%, #e9dfd0 100%);
            -webkit-font-smoothing: antialiased;
        }
        a { color: var(--tide); }

        /* ---- Cover gate ---------------------------------------------------
           An arched sea window: the sun disc and the wave bands are drawn in one
           inline SVG so the cover costs no image request. Checkbox + label only. */
        .pw-cover-switch { position: absolute; width: 1px; height: 1px; margin: 0; opacity: 0; pointer-events: none; }
        .pw-cover-switch:focus-visible ~ .cover .cover__open { outline: 2px solid var(--tide); outline-offset: 5px; }
        .cover {
            position: fixed;
            inset: 0;
            z-index: 60;
            display: grid;
            place-items: center;
            padding: 20px;
            overflow-y: auto;
            background: linear-gradient(180deg, #fdf6ec 0%, #f6e9d8 42%, #dce8e6 100%);
            transition: opacity .55s ease, visibility .55s ease;
        }
        .pw-cover-switch:checked ~ .cover { opacity: 0; visibility: hidden; pointer-events: none; }
        body:has(.pw-cover-switch:not(:checked)) { overflow: hidden; }

        .cover__inner {
            position: relative;
            width: min(100%, 24rem);
            padding: 2.6rem 1.6rem 2.2rem;
            border: 1px solid var(--line);
            border-radius: 12rem 12rem 18px 18px;
            background: rgba(253, 250, 244, .72);
            text-align: center;
        }
        .cover__scene { display: block; width: 108px; margin: 0 auto 1.6rem; }
        .cover__label {
            margin: 0;
            font-family: var(--sans);
            font-size: .56rem;
            letter-spacing: .42em;
            text-transform: uppercase;
            color: var(--muted);
        }
        .cover__names {
            margin: 1rem 0 0;
            font-size: clamp(1.35rem, 6vw, 1.9rem);
            font-weight: 400;
            line-height: 1.4;
            letter-spacing: .05em;
        }
        .cover__names .amp { color: var(--sunset); font-style: italic; }
        .cover__date {
            margin: 1rem 0 0;
            font-family: var(--sans);
            font-size: .6rem;
            letter-spacing: .3em;
            text-transform: uppercase;
            color: var(--tide);
        }
        .cover__to {
            margin: 1.7rem 0 0;
            font-family: var(--sans);
            font-size: .54rem;
            letter-spacing: .26em;
            text-transform: uppercase;
            color: var(--muted);
        }
        .cover__guest { margin: .45rem 0 0; font-size: 1.02rem; }
        .cover__open {
            display: inline-block;
            margin-top: 1.7rem;
            padding: .7rem 1.5rem;
            border: 1px solid var(--tide);
            border-radius: 999px;
            background: rgba(44, 111, 116, .08);
            color: var(--tide);
            font-family: var(--sans);
            font-size: .6rem;
            letter-spacing: .24em;
            text-transform: uppercase;
            cursor: pointer;
        }
        .cover__open:hover { background: var(--tide); color: #fdfaf4; }

        /* ---- Page ---- */
        .preview-banner {
            padding: 10px 16px;
            background: var(--ink);
            color: var(--shell);
            font-family: var(--sans);
            font-size: 13px;
            text-align: center;
        }
        .page { max-width: 600px; margin: 0 auto; padding: 0 22px 70px; }

        .divider { display: block; width: 100%; margin: 0 auto; color: var(--tide); opacity: .5; }

        .hero {
            margin-top: 46px;
            padding: 2.4rem 1.4rem 2rem;
            border: 1px solid var(--line);
            border-radius: 14rem 14rem 22px 22px;
            background:
                radial-gradient(circle at 50% 22%, rgba(224, 160, 106, .34), rgba(224, 160, 106, 0) 62%),
                rgba(253, 250, 244, .8);
            text-align: center;
        }
        .hero__eyebrow {
            margin: 0;
            font-family: var(--sans);
            font-size: .56rem;
            letter-spacing: .42em;
            text-transform: uppercase;
            color: var(--muted);
        }
        h1.names {
            margin: .9rem 0 0;
            font-size: clamp(1.6rem, 7vw, 2.5rem);
            font-weight: 400;
            line-height: 1.32;
            letter-spacing: .02em;
        }
        h1.names .amp { color: var(--sunset); font-style: italic; }
        .hero__date {
            margin: 1rem 0 0;
            font-family: var(--sans);
            font-size: .6rem;
            letter-spacing: .3em;
            text-transform: uppercase;
            color: var(--tide);
        }
        .greeting { margin: 1.6rem auto 0; max-width: 30rem; font-size: .95rem; color: var(--muted); }
        .quote {
            max-width: 30rem;
            margin: 2rem auto 0;
            padding-top: 1.1rem;
            border-top: 1px solid var(--line-soft);
            font-size: .92rem;
            font-style: italic;
            color: var(--ink);
        }
        .quote cite {
            display: block;
            margin-top: .5rem;
            font-family: var(--sans);
            font-style: normal;
            font-size: .56rem;
            letter-spacing: .24em;
            text-transform: uppercase;
            color: var(--tide);
        }

        .block { margin-top: 3.2rem; }
        .block__head { text-align: center; margin-bottom: 1.5rem; }
        .block__index {
            font-family: var(--sans);
            font-size: .54rem;
            letter-spacing: .3em;
            color: var(--sunset);
        }
        .block__title {
            margin: .35rem 0 0;
            font-size: 1.45rem;
            font-weight: 400;
            letter-spacing: .06em;
        }
        .panel {
            padding: 1.6rem 1.35rem;
            border: 1px solid var(--line-soft);
            border-radius: var(--pw-radius);
            background: rgba(253, 250, 244, .74);
        }
        .panel + .panel { margin-top: 1rem; }
        .person { text-align: center; }
        .person__role {
            margin: 0;
            font-family: var(--sans);
            font-size: .54rem;
            letter-spacing: .3em;
            text-transform: uppercase;
            color: var(--sunset);
        }
        .person__name { margin: .6rem 0 0; font-size: 1.35rem; letter-spacing: .02em; }
        .person__parents { margin: .5rem auto 0; max-width: 23rem; font-size: .84rem; color: var(--muted); }
        .person__bio { margin: .5rem auto 0; max-width: 23rem; font-size: .84rem; color: var(--muted); font-style: italic; }
        .story__heading { margin: 0 0 .7rem; font-size: 1.15rem; }
        .story__text { margin: 0; font-size: .93rem; color: #2c4a4d; }
        .event__day {
            margin: 0;
            font-family: var(--sans);
            font-size: .54rem;
            letter-spacing: .28em;
            text-transform: uppercase;
            color: var(--tide);
        }
        .event__title { margin: .55rem 0 0; font-size: 1.3rem; font-weight: 400; }
        .event__when { margin: .35rem 0 0; font-family: var(--sans); font-size: .8rem; color: var(--muted); }
        .event__venue { margin: .8rem 0 0; font-size: 1rem; }
        .event__address { margin: .1rem 0 0; font-size: .85rem; color: var(--muted); }
        .event__map {
            display: inline-block;
            margin-top: .9rem;
            padding: .55rem 1.1rem;
            border: 1px solid var(--tide);
            border-radius: 999px;
            font-family: var(--sans);
            font-size: .58rem;
            letter-spacing: .2em;
            text-transform: uppercase;
            text-decoration: none;
        }
        .event__map:hover { background: var(--tide); color: #fdfaf4; }

        footer {
            margin-top: 3.4rem;
            padding-top: 1.4rem;
            border-top: 1px solid var(--line-soft);
            text-align: center;
            font-family: var(--sans);
            font-size: .56rem;
            letter-spacing: .24em;
            text-transform: uppercase;
            color: var(--muted);
        }
        footer p { margin: 0 0 .3rem; }

        @media (min-width: 640px) {
            .couple-panels { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
            .page { padding-left: 26px; padding-right: 26px; }
        }
        @media (max-width: 420px) {
            .page { padding: 0 16px 62px; }
            .cover__inner, .hero { padding: 2rem 1.1rem 1.7rem; }
            .panel { padding: 1.3rem 1.05rem; }
            .person__name, .event__venue, .person__parents { overflow-wrap: anywhere; }
        }
        @media (prefers-reduced-motion: reduce) {
            .cover { transition: none; }
        }
        @media print {
            .cover, .pw-cover-switch { display: none !important; }
            body { background: #fff; }
            .cover__inner, .hero, .panel { border-radius: 0; background: #fff; }
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
        <input class="pw-cover-switch" type="checkbox" id="pesisir-cover" aria-label="Buka undangan">
        <div class="cover">
            <div class="cover__inner">
                {{-- Sunset over a sea of three wave bands. --}}
                <svg class="cover__scene" viewBox="0 0 120 120" fill="none" aria-hidden="true" focusable="false">
                    <circle cx="60" cy="46" r="19" fill="#e0a06a" opacity=".55"/>
                    <path d="M60 27a19 19 0 0 1 19 19H41a19 19 0 0 1 19-19z" fill="#cf7f45" opacity=".65"/>
                    <path d="M10 76c12-8 22 8 34 0s22 8 34 0 22 8 32 0" stroke="#2c6f74" stroke-width="1.4" stroke-linecap="round"/>
                    <path d="M10 89c12-8 22 8 34 0s22 8 34 0 22 8 32 0" stroke="#2c6f74" stroke-width="1.4" stroke-linecap="round" opacity=".7"/>
                    <path d="M10 102c12-8 22 8 34 0s22 8 34 0 22 8 32 0" stroke="#2c6f74" stroke-width="1.4" stroke-linecap="round" opacity=".4"/>
                </svg>

                <p class="cover__label">{{ $cover['title'] ?? 'Undangan Pernikahan' }}</p>

                <h2 class="cover__names">
                    @forelse ($nicknames as $nickname)
                        {{ $nickname }}@if (! $loop->last) <span class="amp">&amp;</span>@endif
                    @empty
                        {{ $invitation->coupleHeadline() ?: 'Mempelai' }}
                    @endforelse
                </h2>

                @if ($date !== null)
                    <p class="cover__date">{{ $date->copy()->locale('id')->translatedFormat('d F Y') }}</p>
                @endif

                <p class="cover__to">Kepada Yth.</p>
                <p class="cover__guest">{{ $recipient ?? 'Bapak/Ibu/Saudara/i' }}</p>

                <label class="cover__open" for="pesisir-cover">Buka Undangan</label>
            </div>
        </div>

        @include('invitations.partials.demo-bar')
        @include('invitations.partials.showcase-bar')
        @include('invitations.partials.personal-bar')
        @if ($preview ?? false)
            <div class="preview-banner">Pratinjau draf — halaman ini belum diterbitkan dan tidak dapat diakses tamu.</div>
        @endif

        <div class="hero-arch"><img src="{{ $heroImage }}" alt="Foto Mempelai" width="400" height="500"></div>
        <section class="hero" aria-label="Pembuka">
            <p class="hero__eyebrow">{{ $cover['title'] ?? 'The Wedding Of' }}</p>

            <h1 class="names">
                @forelse ($nicknames as $nickname)
                    {{ $nickname }}@if (! $loop->last) <span class="amp">&amp;</span>@endif
                @empty
                    {{ $invitation->coupleHeadline() ?: 'Mempelai' }}
                @endforelse
            </h1>

            @if ($date !== null)
                <p class="hero__date">{{ $date->copy()->locale('id')->translatedFormat('l, d F Y') }}</p>
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

        <svg class="divider" viewBox="0 0 600 26" fill="none" stroke="currentColor" stroke-width="1.2"
             stroke-linecap="round" aria-hidden="true" focusable="false">
            <path d="M0 18c50-12 100 12 150 0s100 12 150 0 100 12 150 0 100 12 150 0"/>
        </svg>

        @if ($couple !== [])
            <section class="block" id="mempelai" aria-label="Mempelai">
                <div class="block__head">
                    <p class="block__index">01</p>
                    <h2 class="block__title">Mempelai</h2>
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
                    <h2 class="block__title">Cerita Kami</h2>
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
                </div>

                @foreach ($events as $event)
                    <article class="panel">
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
