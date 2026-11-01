@php
    $cover = $invitation->content['cover'] ?? [];
    $story = $invitation->content['story'] ?? null;
    $features = $invitation->content['features'] ?? [];
    $couple = $invitation->couple;
    $events = $invitation->events;
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
    $hero = $events[0] ?? [];

    // Snapshot timestamps are ISO strings; anything unparseable is dropped
    // instead of being printed as a broken date.
    $parse = static function (mixed $value): ?\Illuminate\Support\Carbon {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    };

    $heroStart = $parse($hero['starts_at'] ?? null);

    $names = array_values(array_filter(array_map(
        static fn (array $person): string => trim((string) ($person['display_name'] ?? '')),
        $couple,
    )));
@endphp
<!DOCTYPE html>
<html lang="id" data-template="sage-keepsake.v1">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $invitation->coupleHeadline() ?: 'Undangan Pernikahan' }}</title>
    @include('invitations.partials.head-meta')
    <style>
        :root {
            --sage: #a9b79e;
            --sage-deep: #7e9474;
            --teal: #34524a;
            --teal-soft: #4d6b61;
            --cream: #f8f3e8;
            --ink: #2f2b25;
            --muted: #6b6a5e;
            --blush: #dcbdb3;
            --serif: "Iowan Old Style", "Palatino Linotype", Palatino, Georgia, serif;
            --script: "Segoe Script", "Bradley Hand", "Snell Roundhand", "Apple Chancery", "Lucida Handwriting", cursive;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--sage);
            color: var(--ink);
            font-family: var(--serif);
            line-height: 1.75;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }
        a { color: var(--teal); }

        /* The stage is the printed card: a dark sage rail on the left and the
           sheet on the right. Clipping the stage keeps the small rotation from
           creating a sideways scrollbar on a phone. */
        .stage {
            display: grid;
            grid-template-columns: 46px minmax(0, 1fr);
            min-height: 100vh;
            background: linear-gradient(120deg, var(--sage) 0%, #b3bfa7 55%, var(--sage) 100%);
            overflow: hidden;
        }
        .rail {
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--teal);
            color: #eef1e6;
        }
        .rail span {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            font-size: .62rem;
            letter-spacing: .46em;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .stage__inner { padding: 18px 12px 32px; }

        .paper {
            position: relative;
            max-width: 46rem;
            margin: 0 auto;
            padding: clamp(2.6rem, 7vw, 3.6rem) clamp(1.6rem, 5.5vw, 3rem) clamp(1.8rem, 5.5vw, 3rem);
            background:
                radial-gradient(circle at 18% 8%, rgba(255, 255, 255, .75), transparent 42%),
                radial-gradient(circle at 82% 92%, rgba(201, 184, 152, .28), transparent 46%),
                var(--cream);
            box-shadow: 0 22px 44px -30px rgba(38, 55, 42, .85), 0 2px 0 rgba(255, 255, 255, .5) inset;
            transform: rotate(-1.6deg);
        }
        .paper__inner { position: relative; z-index: 1; }

        .spray {
            position: absolute;
            width: clamp(76px, 23%, 132px);
            height: auto;
            pointer-events: none;
            opacity: .62;
            z-index: 0;
        }
        .spray--tl { top: -10px; left: -12px; transform: rotate(-10deg); }
        .spray--br { right: -12px; bottom: -10px; transform: rotate(170deg); }
        .spray__wash { fill: var(--sage-deep); opacity: .28; }
        .spray__stem { fill: none; stroke: var(--sage-deep); stroke-width: 1.5; stroke-linecap: round; }
        .spray__leaf { fill: #a7b99c; opacity: .85; }
        .spray__petal { fill: var(--blush); }
        .spray__core { fill: #c8a86a; }

        /* ---- Type ------------------------------------------------------- */
        .eyebrow {
            margin: 0;
            font-size: .62rem;
            letter-spacing: .34em;
            text-transform: uppercase;
            color: var(--sage-deep);
        }
        .columns {
            display: grid;
            gap: 2rem;
            margin-top: clamp(1.4rem, 5vw, 2.4rem);
        }
        .col--couple { text-align: center; }

        .intro { margin: 0 auto; max-width: 20rem; font-size: .88rem; color: var(--muted); }
        .couple {
            margin: 1.1rem 0 0;
            font-family: var(--script);
            font-style: italic;
            font-weight: 400;
            font-size: clamp(1.9rem, 7vw, 2.6rem);
            line-height: 1.2;
            color: var(--ink);
        }
        .couple span { display: block; }
        .couple .and {
            margin: .15em 0;
            font-family: var(--serif);
            font-size: .34em;
            letter-spacing: .42em;
            text-transform: uppercase;
            color: var(--sage-deep);
        }
        .parents { margin: .5rem 0 0; font-size: .86rem; color: var(--muted); }

        .quote { margin: 1.6rem auto 0; max-width: 21rem; font-size: .88rem; font-style: italic; color: var(--muted); }
        .quote cite { display: block; margin-top: .45rem; font-size: .62rem; font-style: normal; letter-spacing: .24em; text-transform: uppercase; color: var(--sage-deep); }

        .col--when { border-top: 1px solid rgba(126, 148, 116, .45); padding-top: 1.5rem; }
        .kicker {
            margin: 0;
            font-size: .62rem;
            letter-spacing: .34em;
            text-transform: uppercase;
            color: var(--sage-deep);
        }
        .kicker::after { content: " \2022"; }
        .day { margin: .5rem 0 0; font-size: .8rem; letter-spacing: .3em; text-transform: uppercase; color: var(--teal-soft); }
        .date {
            margin: .1rem 0 0;
            font-size: clamp(1.15rem, 3.4vw, 1.7rem);
            letter-spacing: .04em;
            text-transform: uppercase;
            text-wrap: balance;
            color: var(--teal);
        }
        .event { margin-top: 1.3rem; }
        .event h2 {
            margin: 0;
            font-size: .66rem;
            font-weight: 400;
            letter-spacing: .3em;
            text-transform: uppercase;
            color: var(--sage-deep);
        }
        .event .time { margin: .15rem 0 0; font-size: .95rem; }
        .event .place { margin: .15rem 0 0; font-size: .9rem; color: var(--muted); }
        .map-link {
            display: inline-block;
            margin-top: .35rem;
            padding-bottom: 1px;
            border-bottom: 1px solid var(--sage-deep);
            font-size: .78rem;
            color: var(--teal-soft);
            text-decoration: none;
        }
        .closing { margin: 1.6rem 0 0; font-size: .88rem; color: var(--muted); }
        .regards { margin: .8rem 0 0; font-size: .88rem; }
        .regards .names { display: block; margin-top: .2rem; font-family: var(--script); font-style: italic; font-size: 1.3rem; color: var(--ink); }

        .story { margin-top: 2rem; padding-top: 1.4rem; border-top: 1px solid rgba(126, 148, 116, .45); }
        .story h2 { margin: 0 0 .5rem; font-size: .66rem; font-weight: 400; letter-spacing: .3em; text-transform: uppercase; color: var(--sage-deep); }
        .story p { margin: 0; font-size: .9rem; color: var(--muted); }

        footer {
            margin-top: 2.4rem;
            text-align: center;
            font-size: .6rem;
            letter-spacing: .3em;
            text-transform: uppercase;
            color: var(--sage-deep);
        }
        .preview-banner {
            margin: 0 auto 16px;
            max-width: 46rem;
            padding: 10px 14px;
            background: var(--teal);
            color: #eef1e6;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            font-size: .78rem;
            letter-spacing: .03em;
            text-align: center;
        }

        @media (min-width: 720px) {
            .stage { grid-template-columns: 62px minmax(0, 1fr); }
            .stage__inner { padding: 34px 30px 48px; }
            .columns { grid-template-columns: 1fr 1fr; gap: 2.6rem; }
            .col--when { border-top: 0; padding-top: 0; border-left: 1px solid rgba(126, 148, 116, .45); padding-left: 2.6rem; }
            .rail span { font-size: .68rem; letter-spacing: .5em; }
        }
        @media (max-width: 380px) {
            .stage { grid-template-columns: 38px minmax(0, 1fr); }
            .rail span { letter-spacing: .3em; font-size: .56rem; }
        }
        @media (prefers-reduced-motion: reduce) {
            .paper { transform: none; }
        }
        @media print {
            .stage { background: #fff; }
            .paper { box-shadow: none; transform: none; }
            .rail { background: none; color: var(--teal); }
            .spray { opacity: .55; }
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
    <div class="stage">
        <div class="rail" aria-hidden="true"><span>Mohon Doa Restu</span></div>

        <div class="stage__inner">
            @include('invitations.partials.demo-bar')
            @include('invitations.partials.showcase-bar')
            @include('invitations.partials.personal-bar')
            @if ($preview ?? false)
                <div class="preview-banner">Pratinjau draf — halaman ini belum diterbitkan dan tidak dapat diakses tamu.</div>
            @endif

            <article class="paper">
                @include('invitations.partials.sage-spray', ['corner' => 'tl'])
                @include('invitations.partials.sage-spray', ['corner' => 'br'])

                <div class="paper__inner">
                    <div class="columns">
                        <section class="col col--couple" id="mempelai" aria-label="Mempelai">
                            @if (! empty($cover['greeting']))
                                <p class="intro">{{ $cover['greeting'] }}</p>
                            @endif

                            <div class="hero-arch" style="margin-bottom: 1.5rem;">
                                <img src="{{ $heroImage }}" alt="Foto Mempelai" width="400" height="500">
                            </div>
                            <h1 class="couple">
                                @forelse ($names as $name)
                                    <span>{{ $name }}</span>
                                    @if (! $loop->last)
                                        <span class="and" aria-hidden="true">&amp;</span>
                                    @endif
                                @empty
                                    <span>{{ $invitation->coupleHeadline() ?: 'Mempelai' }}</span>
                                @endforelse
                            </h1>
        @if ($recipient !== null)
            <div class="personal-badge" style="text-align: center; margin: 18px auto; max-width: 20rem; padding: 12px 16px; border: 1px dashed rgba(169, 139, 95, .4); border-radius: 8px; background: rgba(255, 255, 255, .7);">
                <p style="margin: 0; font-size: .62rem; text-transform: uppercase; letter-spacing: .22em; opacity: .75;">Kepada Yth. Bapak/Ibu/Saudara/i:</p>
                <p style="margin: 4px 0 0; font-size: 1.15rem; font-weight: 500;">{{ $recipient }}</p>
            </div>
        @endif


                            @foreach ($couple as $person)
                                @if (! empty($person['parents']))
                                    <p class="parents">
                                        {{ $person['type'] === 'groom' ? 'Putra' : 'Putri' }} dari {{ $person['parents'] }}
                                    </p>
                                @endif
                            @endforeach

                            @if (! empty($cover['quote']))
                                <blockquote class="quote">
                                    {{ $cover['quote'] }}
                                    @if (! empty($cover['quote_source']))
                                        <cite>{{ $cover['quote_source'] }}</cite>
                                    @endif
                                </blockquote>
                            @endif
                        </section>

                        <section class="col col--when" id="acara" aria-label="Waktu dan tempat">
                            <p class="kicker">Kami</p>

                            @if ($heroStart !== null)
                                <p class="day">{{ $heroStart->copy()->locale('id')->translatedFormat('l') }}</p>
                                <p class="date">{{ $heroStart->copy()->locale('id')->translatedFormat('d F Y') }}</p>
                            @endif

                            @if (($features['show_events'] ?? true) && $events !== [])
                                @foreach ($events as $event)
                                    @php $starts = $parse($event['starts_at'] ?? null); @endphp
                                    <div class="event">
                                        <h2>{{ $event['title'] ?? '' }}</h2>
                                        <p class="time">
                                            @if ($starts !== null)
                                                Pukul {{ $starts->copy()->locale('id')->format('H.i') }}
                                            @elseif (! empty($event['starts_label']))
                                                {{ $event['starts_label'] }}
                                            @endif
                                        </p>
                                        <p class="place">
                                            {{ $event['venue_name'] ?? '' }}@if (! empty($event['address']))<br>{{ $event['address'] }}@endif
                                        </p>
                                        @if (($features['show_map'] ?? true) && ! empty($event['map_url']))
                                            <a class="map-link" href="{{ $event['map_url'] }}" target="_blank" rel="noopener noreferrer">Lihat peta &rarr;</a>
                                        @endif
                                    </div>
                                @endforeach
                            @endif

                            <p class="closing">
                                Merupakan suatu kehormatan bagi kami apabila Bapak/Ibu/Saudara/i berkenan hadir.
                            </p>
                            <p class="regards">
                                Hormat kami,
                                <span class="names">{{ $invitation->coupleHeadline() ?: 'Mempelai' }}</span>
                            </p>
                        </section>
                    </div>

                    @if (($features['show_story'] ?? true) && ! empty($story['body']))
                        <section class="story" aria-label="Cerita">
                            <h2>{{ $story['heading'] ?? 'Cerita Kami' }}</h2>
                            <p>{!! nl2br(e($story['body'])) !!}</p>
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

                    <footer>{{ $invitation->templateName }}</footer>
                </div>
            </article>
        </div>
    </div>
</body>
</html>
