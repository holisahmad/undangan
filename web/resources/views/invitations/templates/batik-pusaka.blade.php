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

    // The cover date comes from the first published event, never from a second
    // field the couple has to keep in sync.
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
<html lang="id" data-template="batik-pusaka.v1">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $invitation->coupleHeadline() ?: 'Undangan Pernikahan' }}</title>
    @include('invitations.partials.head-meta')
    <style>
        :root {
            --maroon-deep: #3d1216;
            --maroon: #57181d;
            --gold: #c9a227;
            --gold-light: #e6c98a;
            --parchment: #f7efdf;
            --parchment-deep: #efe3cd;
            --ink: #2c1a17;
            --muted: #6e564a;
            --serif: "Iowan Old Style", "Palatino Linotype", Palatino, Georgia, serif;
            --script: "Segoe Script", "Bradley Hand", "Snell Roundhand", "Apple Chancery", "Lucida Handwriting", cursive;
            --pw-accent: #57181d;
            --pw-accent-ink: #fdf6e6;
            --pw-line: rgba(87, 24, 29, .22);
            --pw-surface: rgba(255, 252, 243, .6);
            --pw-radius: 6px;
            --pw-numeric: "Iowan Old Style", Georgia, serif;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background:
                radial-gradient(circle at 50% -12%, rgba(201, 162, 39, .14), transparent 58%),
                var(--parchment);
            color: var(--ink);
            font-family: var(--serif);
            line-height: 1.7;
            -webkit-font-smoothing: antialiased;
        }
        a { color: inherit; }

        /* Batik parang: two counter-running diagonals plus a dot lattice. Drawn
           with gradients so the template ships no binary texture. */
        .batik {
            height: 16px;
            background-image:
                repeating-linear-gradient(52deg, transparent 0 9px, rgba(201, 162, 39, .55) 9px 10px),
                repeating-linear-gradient(-52deg, transparent 0 9px, rgba(201, 162, 39, .35) 9px 10px),
                radial-gradient(circle at 4px 4px, rgba(255, 255, 255, .35) 0 1px, transparent 1.5px);
            background-size: auto, auto, 9px 9px;
            opacity: .85;
        }
        .batik--dark {
            background-image:
                repeating-linear-gradient(52deg, transparent 0 9px, rgba(201, 162, 39, .5) 9px 10px),
                repeating-linear-gradient(-52deg, transparent 0 9px, rgba(201, 162, 39, .28) 9px 10px),
                radial-gradient(circle at 4px 4px, rgba(201, 162, 39, .25) 0 1px, transparent 1.5px);
            background-size: auto, auto, 9px 9px;
        }

        /* ---- Cover gate ---------------------------------------------------
           Checkbox + label only: the flap opens without JavaScript, and the
           invitation underneath is already in the document for crawlers,
           screen readers and print. */
        .pw-cover-switch {
            position: absolute;
            width: 1px;
            height: 1px;
            margin: 0;
            opacity: 0;
            pointer-events: none;
        }
        .pw-cover-switch:focus-visible ~ .cover .cover__open,
        .pw-cover-switch:focus-visible ~ .cover .cover__label { outline: 2px solid var(--gold-light); outline-offset: 6px; }
        .cover {
            position: fixed;
            inset: 0;
            z-index: 60;
            display: grid;
            place-items: center;
            padding: 18px;
            overflow-y: auto;
            background:
                radial-gradient(circle at 50% 12%, rgba(201, 162, 39, .22), transparent 60%),
                var(--maroon-deep);
            transition: opacity .6s ease, visibility .6s ease;
        }
        .pw-cover-switch:checked ~ .cover {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }
        body:has(.pw-cover-switch:not(:checked)) { overflow: hidden; }

        .cover__frame {
            position: relative;
            width: min(100%, 24rem);
            padding: 3.6rem 1.7rem 3rem;
            border: 1px solid rgba(201, 162, 39, .55);
            background:
                radial-gradient(circle at 50% 0%, rgba(201, 162, 39, .16), transparent 55%),
                rgba(61, 18, 22, .6);
            text-align: center;
            color: var(--parchment);
        }
        .cover__frame::before {
            content: "";
            position: absolute;
            inset: 8px;
            border: 1px solid rgba(201, 162, 39, .3);
            pointer-events: none;
        }
        .cover__gunungan { display: block; width: 74px; margin: 0 auto .4rem; color: var(--gold); }
        .cover__label {
            margin: 0;
            font-size: .6rem;
            letter-spacing: .42em;
            text-transform: uppercase;
            color: var(--gold-light);
        }
        .cover__names {
            margin: .9rem 0 0;
            font-family: var(--script);
            font-size: clamp(2.1rem, 9vw, 2.9rem);
            line-height: 1.18;
            font-weight: 400;
            color: var(--parchment);
        }
        .cover__names .amp {
            display: block;
            margin: .1rem 0;
            font-family: var(--serif);
            font-size: .9rem;
            letter-spacing: .3em;
            color: var(--gold);
        }
        .cover__date {
            margin: 1rem 0 0;
            font-size: .74rem;
            letter-spacing: .3em;
            text-transform: uppercase;
            color: var(--gold-light);
        }
        .cover__rule {
            width: 54px;
            height: 1px;
            margin: 1.5rem auto;
            border: 0;
            background: linear-gradient(90deg, transparent, rgba(201, 162, 39, .8), transparent);
        }
        .cover__to { margin: 0; font-size: .62rem; letter-spacing: .26em; text-transform: uppercase; color: rgba(247, 239, 223, .68); }
        .cover__guest { margin: .45rem 0 0; font-size: 1.05rem; color: var(--parchment); }
        .cover__open {
            display: inline-block;
            margin-top: 1.8rem;
            padding: .72rem 1.9rem;
            border: 1px solid rgba(201, 162, 39, .75);
            border-radius: 2px;
            color: var(--gold-light);
            font-size: .66rem;
            letter-spacing: .26em;
            text-transform: uppercase;
            cursor: pointer;
            transition: background-color .25s ease, color .25s ease;
        }
        .cover__open:hover { background: var(--gold); color: var(--maroon-deep); }

        /* ---- Page --------------------------------------------------------- */
        .preview-banner {
            padding: 10px 16px;
            background: var(--maroon-deep);
            color: var(--parchment);
            font-family: ui-sans-serif, system-ui, sans-serif;
            font-size: 13px;
            text-align: center;
        }
        .page { max-width: 620px; margin: 0 auto; padding: 0 20px 64px; }
        .lead { padding: 46px 0 8px; text-align: center; }
        .eyebrow {
            margin: 0;
            font-size: .62rem;
            letter-spacing: .38em;
            text-transform: uppercase;
            color: var(--maroon);
        }
        h1.names {
            margin: .7rem 0 0;
            font-family: var(--script);
            font-size: clamp(2rem, 8vw, 2.9rem);
            font-weight: 400;
            line-height: 1.2;
        }
        h1.names .amp {
            display: block;
            margin: .15rem 0;
            font-family: var(--serif);
            font-size: .8rem;
            letter-spacing: .32em;
            color: var(--gold);
        }
        .lead__date {
            margin: 1.1rem 0 0;
            font-size: .7rem;
            letter-spacing: .3em;
            text-transform: uppercase;
            color: var(--muted);
        }
        .greeting { margin: 1.1rem auto 0; max-width: 26rem; font-size: .92rem; color: var(--muted); }
        .quote {
            margin: 2rem auto 0;
            max-width: 27rem;
            font-style: italic;
            color: var(--muted);
        }
        .quote cite {
            display: block;
            margin-top: .6rem;
            font-style: normal;
            font-size: .64rem;
            letter-spacing: .22em;
            text-transform: uppercase;
        }
        .leaf {
            margin-top: 2.4rem;
            padding: 2rem 1.5rem;
            border: 1px solid rgba(87, 24, 29, .18);
            background: rgba(255, 252, 243, .72);
            box-shadow: 0 14px 34px rgba(61, 18, 22, .07);
        }
        .leaf__title {
            margin: 0 0 1.2rem;
            font-size: .66rem;
            letter-spacing: .36em;
            text-transform: uppercase;
            text-align: center;
            color: var(--maroon);
        }
        .leaf__title span {
            display: block;
            margin-top: .55rem;
            font-family: var(--serif);
            font-size: 1.35rem;
            letter-spacing: 0;
            text-transform: none;
            color: var(--ink);
        }
        .person { text-align: center; }
        .person + .person { margin-top: 1.6rem; }
        .person__role { margin: 0; font-size: .6rem; letter-spacing: .28em; text-transform: uppercase; color: var(--gold); }
        .person__name {
            margin: .35rem 0 0;
            font-family: var(--script);
            font-size: clamp(1.6rem, 6vw, 2.1rem);
            font-weight: 400;
            line-height: 1.3;
        }
        .person__meta { margin: .5rem auto 0; max-width: 24rem; font-size: .85rem; color: var(--muted); }
        .amp-mark { margin: 1.4rem 0; text-align: center; color: var(--gold); font-size: 1.1rem; letter-spacing: .3em; }
        .story p { margin: 0; font-size: .95rem; color: #4a342c; }
        .event + .event { margin-top: 1.3rem; padding-top: 1.3rem; border-top: 1px solid rgba(87, 24, 29, .12); }
        .event__day {
            margin: 0;
            font-size: .6rem;
            letter-spacing: .28em;
            text-transform: uppercase;
            color: var(--maroon);
        }
        .event__title {
            margin: .45rem 0 0;
            font-family: var(--script);
            font-size: 1.5rem;
            font-weight: 400;
        }
        .event__when { margin: .3rem 0 0; font-size: .9rem; color: var(--ink); }
        .event__venue { margin: .35rem 0 0; font-size: .95rem; }
        .event__address { margin: .15rem 0 0; font-size: .85rem; color: var(--muted); }
        .event__map {
            display: inline-block;
            margin-top: .6rem;
            padding-bottom: 2px;
            border-bottom: 1px solid rgba(87, 24, 29, .35);
            font-size: .72rem;
            letter-spacing: .16em;
            text-transform: uppercase;
            text-decoration: none;
            color: var(--maroon);
        }
        footer { margin-top: 3rem; text-align: center; color: var(--muted); }
        footer .gunungan { display: block; width: 54px; margin: 0 auto .9rem; color: var(--gold); }
        footer p { margin: 0; font-size: .64rem; letter-spacing: .24em; text-transform: uppercase; }

        @media (min-width: 640px) {
            .leaf { padding: 2.4rem 2.2rem; }
            .couple-grid { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 1rem; }
            .couple-grid .amp-mark { margin: 0; }
            .person + .person { margin-top: 0; }
        }
        @media (max-width: 420px) {
            .page { padding: 0 16px 56px; }
            .leaf { padding: 1.6rem 1.15rem; }
            .person__name, .event__venue, .person__meta { overflow-wrap: anywhere; }
        }
        @media (prefers-reduced-motion: reduce) {
            .cover { transition: none; }
            .cover__open { transition: none; }
        }
        @media print {
            .cover, .pw-cover-switch { display: none !important; }
            body { background: #fff; }
            .leaf { box-shadow: none; }
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
        <input class="pw-cover-switch" type="checkbox" id="batik-cover" aria-label="Buka undangan">
        <div class="cover">
            <div class="cover__frame">
                @include('invitations.partials.batik-gunungan', ['class' => 'cover__gunungan'])
                <p class="cover__label">{{ $cover['title'] ?? 'The Wedding Of' }}</p>

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

                <label class="cover__open" for="batik-cover">Buka Undangan</label>
            </div>
        </div>

        @include('invitations.partials.demo-bar')
        @include('invitations.partials.showcase-bar')
        @include('invitations.partials.personal-bar')
        @if ($preview ?? false)
            <div class="preview-banner">Pratinjau draf — halaman ini belum diterbitkan dan tidak dapat diakses tamu.</div>
        @endif

        <div class="batik" aria-hidden="true"></div>

        <div class="hero-arch"><img src="{{ $heroImage }}" alt="Foto Mempelai" width="400" height="500"></div>
        <section class="lead" aria-label="Pembuka">
            <p class="eyebrow">{{ $cover['title'] ?? 'The Wedding Of' }}</p>

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
            <section class="leaf" id="mempelai" aria-label="Mempelai">
                <h2 class="leaf__title">Mempelai <span>Dua Hati, Satu Tujuan</span></h2>

                <div class="couple-grid">
                    @foreach ($couple as $index => $person)
                        @if ($index > 0)
                            <p class="amp-mark" aria-hidden="true">&amp;</p>
                        @endif
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
                    @endforeach
                </div>
            </section>
        @endif

        @if (($features['show_story'] ?? true) && ! empty($story['body']))
            <section class="leaf story" aria-label="Cerita">
                <h2 class="leaf__title">Cerita <span>{{ $story['heading'] ?? 'Perjalanan Kami' }}</span></h2>
                <p>{!! nl2br(e($story['body'])) !!}</p>
            </section>
        @endif

        @if (($features['show_events'] ?? true) && $events !== [])
            <section class="leaf" id="acara" aria-label="Acara">
                <h2 class="leaf__title">Acara <span>Rangkaian Hari Bahagia</span></h2>

                @foreach ($events as $event)
                    <article class="event">
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
            @include('invitations.partials.batik-gunungan', ['class' => 'gunungan'])
            <p>{{ $invitation->templateName }}</p>
        </footer>

        <div class="batik" aria-hidden="true"></div>
    </main>
</body>
</html>
