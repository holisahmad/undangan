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
            .(count($nicknames) > 1 ? '·'.mb_strtoupper(mb_substr($nicknames[1], 0, 1)) : '');
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
<html lang="id" data-template="glasshouse-minimal.v1">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $invitation->coupleHeadline() ?: 'Undangan Pernikahan' }}</title>
    @include('invitations.partials.head-meta')
    <style>
        :root {
            --paper: #f7f6f1;
            --paper-soft: #fdfdfb;
            --ink: #1d211e;
            --muted: #6b736d;
            --sage: #63776a;
            --line: rgba(29, 33, 30, .14);
            --line-soft: rgba(29, 33, 30, .07);
            --serif: "Iowan Old Style", "Palatino Linotype", Palatino, Georgia, serif;
            --sans: ui-sans-serif, system-ui, "Segoe UI", Helvetica, Arial, sans-serif;
            --pw-accent: #1d211e;
            --pw-accent-ink: #fbfbf8;
            --pw-line: rgba(29, 33, 30, .2);
            --pw-surface: rgba(255, 255, 255, .62);
            --pw-radius: 2px;
            --pw-display: "Iowan Old Style", Georgia, serif;
            --pw-numeric: ui-sans-serif, system-ui, "Segoe UI", sans-serif;
            --pw-nav-bg: rgba(251, 251, 247, .93);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: var(--ink);
            font-family: var(--serif);
            line-height: 1.78;
            background:
                linear-gradient(180deg, var(--paper-soft) 0%, var(--paper) 40%, #f2f1ec 100%);
            -webkit-font-smoothing: antialiased;
        }
        a { color: var(--ink); }

        /* ---- Cover gate ---------------------------------------------------
           A conservatory window drawn in hairlines. Checkbox + label only: no
           script is needed to open the invitation and the content below is
           already in the document for crawlers, readers and print. */
        .pw-cover-switch { position: absolute; width: 1px; height: 1px; margin: 0; opacity: 0; pointer-events: none; }
        .pw-cover-switch:focus-visible ~ .cover .cover__open { outline: 2px solid var(--sage); outline-offset: 5px; }
        .cover {
            position: fixed;
            inset: 0;
            z-index: 60;
            display: grid;
            place-items: center;
            padding: 18px;
            overflow-y: auto;
            background: var(--paper-soft);
            transition: opacity .5s ease, visibility .5s ease;
        }
        .cover::before {
            content: "";
            position: absolute;
            inset: 12px;
            border: 1px solid var(--line);
            pointer-events: none;
        }
        .pw-cover-switch:checked ~ .cover { opacity: 0; visibility: hidden; pointer-events: none; }
        body:has(.pw-cover-switch:not(:checked)) { overflow: hidden; }

        .cover__inner { position: relative; width: min(100%, 23rem); text-align: center; }
        .cover__window {
            display: block;
            width: 74px;
            margin: 0 auto 2.4rem;
            color: var(--sage);
            opacity: .55;
        }
        .cover__label {
            position: relative;
            margin: 0;
            font-family: var(--sans);
            font-size: .56rem;
            letter-spacing: .48em;
            text-transform: uppercase;
            color: var(--muted);
        }
        .cover__names {
            position: relative;
            margin: 1.1rem 0 0;
            font-size: clamp(1.5rem, 6.4vw, 2.1rem);
            font-weight: 400;
            line-height: 1.42;
            letter-spacing: .16em;
            text-transform: uppercase;
        }
        .cover__names .amp {
            display: block;
            margin: .15rem 0;
            font-family: var(--sans);
            font-size: .6rem;
            letter-spacing: .4em;
            color: var(--sage);
        }
        .cover__date {
            position: relative;
            margin: 1.3rem 0 0;
            font-family: var(--sans);
            font-size: .58rem;
            letter-spacing: .34em;
            text-transform: uppercase;
            color: var(--muted);
        }
        .cover__rule { width: 26px; height: 1px; margin: 1.8rem auto; border: 0; background: var(--line); }
        .cover__to {
            position: relative;
            margin: 0;
            font-family: var(--sans);
            font-size: .54rem;
            letter-spacing: .3em;
            text-transform: uppercase;
            color: var(--muted);
        }
        .cover__guest { position: relative; margin: .5rem 0 0; font-size: 1rem; letter-spacing: .04em; }
        .cover__open {
            position: relative;
            display: inline-block;
            margin-top: 2rem;
            padding-bottom: 3px;
            border-bottom: 1px solid var(--ink);
            font-family: var(--sans);
            font-size: .58rem;
            letter-spacing: .28em;
            text-transform: uppercase;
            cursor: pointer;
        }
        .cover__open:hover { color: var(--sage); border-color: var(--sage); }

        /* ---- Page ---- */
        .preview-banner {
            padding: 10px 16px;
            background: var(--ink);
            color: var(--paper-soft);
            font-family: var(--sans);
            font-size: 13px;
            text-align: center;
        }
        .page { max-width: 560px; margin: 0 auto; padding: 0 22px 70px; }

        .lead { padding: 64px 0 0; }
        .lead__mark {
            display: flex;
            align-items: center;
            gap: .8rem;
            margin: 0 0 1.8rem;
            font-family: var(--sans);
            font-size: .54rem;
            letter-spacing: .34em;
            text-transform: uppercase;
            color: var(--muted);
        }
        .lead__mark::after { content: ""; flex: 1; height: 1px; background: var(--line); }
        h1.names {
            margin: 0;
            font-size: clamp(1.55rem, 6.6vw, 2.2rem);
            font-weight: 400;
            line-height: 1.4;
            letter-spacing: .16em;
            text-transform: uppercase;
        }
        h1.names .amp {
            display: block;
            margin: .2rem 0;
            font-family: var(--sans);
            font-size: .6rem;
            letter-spacing: .42em;
            color: var(--sage);
        }
        .lead__date {
            margin: 1.4rem 0 0;
            font-family: var(--sans);
            font-size: .58rem;
            letter-spacing: .3em;
            text-transform: uppercase;
            color: var(--muted);
        }
        .greeting { margin: 1.5rem 0 0; max-width: 30rem; font-size: .95rem; color: var(--muted); }
        .quote {
            margin: 2.6rem 0 0;
            padding-left: 1.1rem;
            border-left: 1px solid var(--line);
            font-size: .92rem;
            font-style: italic;
            color: var(--muted);
        }
        .quote cite {
            display: block;
            margin-top: .55rem;
            font-family: var(--sans);
            font-style: normal;
            font-size: .54rem;
            letter-spacing: .26em;
            text-transform: uppercase;
            color: var(--sage);
        }

        .block { margin-top: 3rem; }
        .block__head {
            display: grid;
            grid-template-columns: auto 1fr;
            align-items: baseline;
            gap: .9rem;
            margin-bottom: 1.5rem;
            padding-bottom: .7rem;
            border-bottom: 1px solid var(--line);
        }
        .block__index {
            font-family: var(--sans);
            font-size: .54rem;
            letter-spacing: .2em;
            color: var(--sage);
        }
        .block__title {
            margin: 0;
            font-family: var(--sans);
            font-size: .56rem;
            font-weight: 500;
            letter-spacing: .34em;
            text-transform: uppercase;
            color: var(--muted);
        }
        .panel {
            border: 1px solid var(--line-soft);
            background: rgba(255, 255, 255, .6);
        }
        .panel + .panel { margin-top: 1rem; }
        .person { padding: 1.7rem 1.35rem; text-align: center; }
        .person + .person { border-top: 1px solid var(--line-soft); }
        .person__role {
            margin: 0;
            font-family: var(--sans);
            font-size: .52rem;
            letter-spacing: .32em;
            text-transform: uppercase;
            color: var(--muted);
        }
        .person__name { margin: .55rem 0 0; font-size: 1.3rem; letter-spacing: .06em; }
        .person__meta { margin: .55rem auto 0; max-width: 23rem; font-size: .84rem; color: var(--muted); }
        .story__body { padding: 1.6rem 1.35rem; }
        .story__heading { margin: 0 0 .7rem; font-size: 1.15rem; letter-spacing: .04em; }
        .story__text { margin: 0; font-size: .93rem; color: #3d443f; }
        .event { padding: 1.6rem 1.35rem; }
        .event__day {
            margin: 0;
            font-family: var(--sans);
            font-size: .54rem;
            letter-spacing: .3em;
            text-transform: uppercase;
            color: var(--sage);
        }
        .event__title { margin: .6rem 0 0; font-size: 1.22rem; font-weight: 400; letter-spacing: .04em; }
        .event__when { margin: .3rem 0 0; font-family: var(--sans); font-size: .78rem; color: var(--muted); }
        .event__venue { margin: .7rem 0 0; font-size: .95rem; }
        .event__address { margin: .1rem 0 0; font-size: .84rem; color: var(--muted); }
        .event__map {
            display: inline-block;
            margin-top: .8rem;
            padding-bottom: 2px;
            border-bottom: 1px solid var(--line);
            font-family: var(--sans);
            font-size: .56rem;
            letter-spacing: .24em;
            text-transform: uppercase;
            text-decoration: none;
        }
        .event__map:hover { border-color: var(--sage); color: var(--sage); }

        footer {
            margin-top: 3.4rem;
            padding-top: 1.6rem;
            border-top: 1px solid var(--line);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            font-family: var(--sans);
            font-size: .54rem;
            letter-spacing: .26em;
            text-transform: uppercase;
            color: var(--muted);
        }
        footer p { margin: 0; }

        @media (min-width: 640px) {
            .couple-panels { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
            .couple-panels .panel + .panel { margin-top: 0; }
            .couple-panels .person + .person { border-top: 0; }
            .page { padding-left: 26px; padding-right: 26px; }
        }
        @media (max-width: 420px) {
            .page { padding: 0 17px 62px; }
            .person, .event, .story__body { padding: 1.35rem 1.1rem; }
            .person__name, .event__venue, .person__meta { overflow-wrap: anywhere; }
            footer { flex-direction: column; align-items: flex-start; gap: .4rem; }
        }
        @media (prefers-reduced-motion: reduce) {
            .cover { transition: none; }
        }
        @media print {
            .cover, .pw-cover-switch { display: none !important; }
            body { background: #fff; }
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
        <input class="pw-cover-switch" type="checkbox" id="glasshouse-cover" aria-label="Buka undangan">
        <div class="cover">
            <div class="cover__inner">
                @include('invitations.partials.glasshouse-window', ['class' => 'cover__window'])
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

                @if ($date !== null)
                    <p class="cover__date">{{ $date->copy()->locale('id')->translatedFormat('d F Y') }}</p>
                @endif

                <hr class="cover__rule">

                <p class="cover__to">Kepada Yth.</p>
                <p class="cover__guest">{{ $recipient ?? 'Bapak/Ibu/Saudara/i' }}</p>

                <label class="cover__open" for="glasshouse-cover">Buka Undangan</label>
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
            <p class="lead__mark">{{ $initial !== '' ? $initial : ($cover['title'] ?? 'Undangan') }} &middot; {{ $cover['title'] ?? 'The Wedding Of' }}</p>

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
                    <span class="block__index">01</span>
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
                                    <p class="person__meta">Putra/Putri dari {{ $person['parents'] }}</p>
                                @endif
                                @if (! empty($person['biography']))
                                    <p class="person__meta">{{ $person['biography'] }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if (($features['show_story'] ?? true) && ! empty($story['body']))
            <section class="block story" aria-label="Cerita">
                <div class="block__head">
                    <span class="block__index">02</span>
                    <h2 class="block__title">Cerita</h2>
                </div>

                <div class="panel">
                    <div class="story__body">
                        @if (! empty($story['heading']))
                            <p class="story__heading">{{ $story['heading'] }}</p>
                        @endif
                        <p class="story__text">{!! nl2br(e($story['body'])) !!}</p>
                    </div>
                </div>
            </section>
        @endif

        @if (($features['show_events'] ?? true) && $events !== [])
            <section class="block" id="acara" aria-label="Acara">
                <div class="block__head">
                    <span class="block__index">03</span>
                    <h2 class="block__title">Acara</h2>
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
