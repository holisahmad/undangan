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
<html lang="id" data-template="velvet-royal.v1">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $invitation->coupleHeadline() ?: 'Undangan Pernikahan' }}</title>
    @include('invitations.partials.head-meta')
    <style>
        :root {
            --velvet: #2c0f26;
            --velvet-deep: #1b0817;
            --plum: #5a1f47;
            --gold: #c9a24b;
            --gold-soft: #e6cf95;
            --ink: #f8ecf4;
            --muted: #c8aec0;
            --line: rgba(201, 162, 75, .38);
            --line-soft: rgba(201, 162, 75, .18);
            --serif: "Baskerville Old Face", "Bodoni MT", Didot, "Times New Roman", Georgia, serif;
            --sans: ui-sans-serif, system-ui, "Segoe UI", Helvetica, Arial, sans-serif;
            --pw-accent: #c9a24b;
            --pw-accent-ink: #2c0f26;
            --pw-line: rgba(201, 162, 75, .45);
            --pw-surface: rgba(90, 31, 71, .35);
            --pw-radius: 8px;
            --pw-display: "Baskerville Old Face", Didot, "Times New Roman", Georgia, serif;
            --pw-numeric: ui-sans-serif, system-ui, "Segoe UI", sans-serif;
            --pw-nav-bg: rgba(27, 8, 23, .9);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: var(--ink);
            font-family: var(--serif);
            line-height: 1.78;
            /* Velvet reads as light falling on nap: one radial highlight over a
               deep base, plus a fine woven texture from a 3px repeating pattern. */
            background:
                radial-gradient(120% 80% at 50% 0%, rgba(201, 162, 75, .14), rgba(201, 162, 75, 0) 60%),
                repeating-linear-gradient(115deg, rgba(255, 255, 255, .014) 0 3px, transparent 3px 6px),
                linear-gradient(180deg, var(--plum) 0%, var(--velvet) 34%, var(--velvet-deep) 100%);
            background-attachment: fixed;
            -webkit-font-smoothing: antialiased;
        }
        a { color: var(--gold-soft); }

        .corner {
            position: absolute;
            width: 54px;
            height: 54px;
            color: var(--gold);
            opacity: .8;
            pointer-events: none;
        }
        .corner--tl { top: 10px; left: 10px; }
        .corner--tr { top: 10px; right: 10px; transform: scaleX(-1); }
        .corner--bl { bottom: 10px; left: 10px; transform: scaleY(-1); }
        .corner--br { bottom: 10px; right: 10px; transform: scale(-1); }
        .rule {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .7rem;
            margin: 1.3rem auto;
            color: var(--gold);
        }
        .rule::before, .rule::after { content: ""; width: 58px; height: 1px; background: var(--line); }
        .rule__lozenge { width: 7px; height: 7px; border: 1px solid var(--gold); transform: rotate(45deg); }

        /* ---- Cover gate: a wax-sealed flap. ---- */
        .pw-cover-switch { position: absolute; width: 1px; height: 1px; margin: 0; opacity: 0; pointer-events: none; }
        .pw-cover-switch:focus-visible ~ .cover .cover__open { outline: 2px solid var(--gold); outline-offset: 6px; }
        .cover {
            position: fixed;
            inset: 0;
            z-index: 60;
            display: grid;
            place-items: center;
            padding: 20px;
            overflow-y: auto;
            background: linear-gradient(180deg, var(--plum) 0%, var(--velvet) 45%, var(--velvet-deep) 100%);
            transition: opacity .55s ease, visibility .55s ease;
        }
        .pw-cover-switch:checked ~ .cover { opacity: 0; visibility: hidden; pointer-events: none; }
        body:has(.pw-cover-switch:not(:checked)) { overflow: hidden; }

        .cover__inner {
            position: relative;
            width: min(100%, 25rem);
            padding: 3.2rem 1.7rem 2.8rem;
            border: 1px solid var(--gold);
            background: rgba(44, 15, 38, .55);
            text-align: center;
        }
        .cover__inner::before {
            content: "";
            position: absolute;
            inset: 7px;
            border: 1px solid var(--line-soft);
            pointer-events: none;
        }
        .cover__medallion {
            display: grid;
            place-items: center;
            width: 74px;
            height: 74px;
            margin: 0 auto 1.7rem;
            border: 1px solid var(--gold);
            border-radius: 50%;
            color: var(--gold-soft);
            font-family: var(--serif);
            font-size: 1.15rem;
            letter-spacing: .08em;
        }
        .cover__label {
            margin: 0;
            font-family: var(--sans);
            font-size: .55rem;
            letter-spacing: .44em;
            text-transform: uppercase;
            color: var(--muted);
        }
        .cover__names {
            margin: 1.2rem 0 0;
            font-size: clamp(1.4rem, 6.4vw, 2rem);
            font-weight: 400;
            line-height: 1.42;
            letter-spacing: .1em;
        }
        .cover__names .amp { color: var(--gold); font-style: italic; letter-spacing: 0; }
        .cover__date {
            margin: 1.2rem 0 0;
            font-family: var(--sans);
            font-size: .58rem;
            letter-spacing: .32em;
            text-transform: uppercase;
            color: var(--gold-soft);
        }
        .cover__to {
            margin: 1.9rem 0 0;
            font-family: var(--sans);
            font-size: .53rem;
            letter-spacing: .28em;
            text-transform: uppercase;
            color: var(--muted);
        }
        .cover__guest { margin: .55rem 0 0; font-size: 1.08rem; letter-spacing: .04em; }
        .cover__open {
            display: inline-block;
            margin-top: 1.9rem;
            padding: .8rem 1.9rem;
            border: 1px solid var(--gold);
            border-radius: 999px;
            background: var(--gold);
            color: var(--velvet);
            font-family: var(--sans);
            font-size: .6rem;
            font-weight: 600;
            letter-spacing: .26em;
            text-transform: uppercase;
            cursor: pointer;
        }
        .cover__open:hover { background: var(--gold-soft); border-color: var(--gold-soft); }

        /* ---- Page ---- */
        .preview-banner {
            padding: 10px 16px;
            background: var(--gold);
            color: var(--velvet-deep);
            font-family: var(--sans);
            font-size: 13px;
            text-align: center;
        }
        .page { max-width: 580px; margin: 0 auto; padding: 0 22px 70px; }

        .lead {
            position: relative;
            margin-top: 46px;
            padding: 3rem 1.5rem 2.4rem;
            border: 1px solid var(--line);
            background: rgba(44, 15, 38, .42);
            text-align: center;
        }
        .lead::before {
            content: "";
            position: absolute;
            inset: 6px;
            border: 1px solid var(--line-soft);
            pointer-events: none;
        }
        .lead__eyebrow {
            position: relative;
            margin: 0;
            font-family: var(--sans);
            font-size: .55rem;
            letter-spacing: .44em;
            text-transform: uppercase;
            color: var(--muted);
        }
        h1.names {
            position: relative;
            margin: 1.2rem 0 0;
            font-size: clamp(1.7rem, 7.4vw, 2.6rem);
            font-weight: 400;
            line-height: 1.3;
            letter-spacing: .08em;
        }
        h1.names .amp { color: var(--gold); font-style: italic; letter-spacing: 0; }
        .lead__date {
            position: relative;
            margin: 0;
            font-family: var(--sans);
            font-size: .58rem;
            letter-spacing: .3em;
            text-transform: uppercase;
            color: var(--gold-soft);
        }
        .greeting { position: relative; margin: 1.6rem auto 0; max-width: 30rem; font-size: .95rem; color: var(--muted); }
        .quote {
            position: relative;
            max-width: 30rem;
            margin: 1.6rem auto 0;
            font-size: .93rem;
            font-style: italic;
            color: var(--ink);
        }
        .quote cite {
            display: block;
            margin-top: .55rem;
            font-family: var(--sans);
            font-style: normal;
            font-size: .55rem;
            letter-spacing: .26em;
            text-transform: uppercase;
            color: var(--gold);
        }

        .block { margin-top: 3.2rem; }
        .block__head { text-align: center; margin-bottom: 1.5rem; }
        .block__index {
            font-family: var(--sans);
            font-size: .53rem;
            letter-spacing: .3em;
            text-transform: uppercase;
            color: var(--gold);
        }
        .block__title {
            margin: .5rem 0 0;
            font-size: 1.55rem;
            font-weight: 400;
            letter-spacing: .16em;
            text-transform: uppercase;
        }
        .panel {
            position: relative;
            padding: 1.8rem 1.4rem;
            border: 1px solid var(--line-soft);
            background: rgba(90, 31, 71, .28);
        }
        .panel + .panel { margin-top: 1rem; }
        .person { text-align: center; }
        .person__role {
            margin: 0;
            font-family: var(--sans);
            font-size: .53rem;
            letter-spacing: .32em;
            text-transform: uppercase;
            color: var(--gold);
        }
        .person__name { margin: .7rem 0 0; font-size: 1.4rem; letter-spacing: .08em; }
        .person__parents { margin: .55rem auto 0; max-width: 23rem; font-size: .85rem; color: var(--muted); }
        .person__bio { margin: .55rem auto 0; max-width: 23rem; font-size: .84rem; color: var(--muted); font-style: italic; }
        .story__heading { margin: 0 0 .7rem; font-size: 1.2rem; letter-spacing: .06em; text-align: center; }
        .story__text { margin: 0; font-size: .93rem; color: #ecd9e6; }
        .event { text-align: center; }
        .event__day {
            margin: 0;
            font-family: var(--sans);
            font-size: .53rem;
            letter-spacing: .3em;
            text-transform: uppercase;
            color: var(--gold);
        }
        .event__title { margin: .65rem 0 0; font-size: 1.35rem; font-weight: 400; letter-spacing: .12em; text-transform: uppercase; }
        .event__when { margin: .4rem 0 0; font-family: var(--sans); font-size: .8rem; color: var(--muted); }
        .event__venue { margin: .9rem 0 0; font-size: 1.02rem; letter-spacing: .04em; }
        .event__address { margin: .15rem 0 0; font-size: .85rem; color: var(--muted); }
        .event__map {
            display: inline-block;
            margin-top: 1rem;
            padding: .6rem 1.4rem;
            border: 1px solid var(--gold);
            border-radius: 999px;
            font-family: var(--sans);
            font-size: .58rem;
            letter-spacing: .22em;
            text-transform: uppercase;
            text-decoration: none;
        }
        .event__map:hover { background: var(--gold); color: var(--velvet-deep); }

        footer {
            margin-top: 3.6rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--line-soft);
            text-align: center;
            font-family: var(--sans);
            font-size: .55rem;
            letter-spacing: .26em;
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
            .cover__inner, .lead { padding: 2.3rem 1.1rem 1.9rem; }
            .panel { padding: 1.4rem 1.05rem; }
            .person__name, .event__venue, .person__parents { overflow-wrap: anywhere; }
            .corner { width: 38px; height: 38px; }
        }
        @media (prefers-reduced-motion: reduce) {
            .cover { transition: none; }
        }
        @media print {
            .cover, .pw-cover-switch { display: none !important; }
            body { background: #fff; color: #1c1c1c; }
        }

</style>
</head>
<body>
    <main class="page">
        <input class="pw-cover-switch" type="checkbox" id="velvet-cover" aria-label="Buka undangan">
        <div class="cover">
            <div class="cover__inner">
                <svg class="corner corner--tl" viewBox="0 0 54 54" fill="none" stroke="currentColor" stroke-width="1"
                     aria-hidden="true" focusable="false">
                    <path d="M2 52V14C2 7 7 2 14 2h38"/>
                    <path d="M10 52V20c0-6 4-10 10-10h32" opacity=".5"/>
                    <path d="M20 30c6-2 8-4 10-10" opacity=".7"/>
                </svg>
                <svg class="corner corner--tr" viewBox="0 0 54 54" fill="none" stroke="currentColor" stroke-width="1"
                     aria-hidden="true" focusable="false">
                    <path d="M2 52V14C2 7 7 2 14 2h38"/>
                    <path d="M10 52V20c0-6 4-10 10-10h32" opacity=".5"/>
                    <path d="M20 30c6-2 8-4 10-10" opacity=".7"/>
                </svg>
                <svg class="corner corner--bl" viewBox="0 0 54 54" fill="none" stroke="currentColor" stroke-width="1"
                     aria-hidden="true" focusable="false">
                    <path d="M2 52V14C2 7 7 2 14 2h38"/>
                    <path d="M10 52V20c0-6 4-10 10-10h32" opacity=".5"/>
                    <path d="M20 30c6-2 8-4 10-10" opacity=".7"/>
                </svg>
                <svg class="corner corner--br" viewBox="0 0 54 54" fill="none" stroke="currentColor" stroke-width="1"
                     aria-hidden="true" focusable="false">
                    <path d="M2 52V14C2 7 7 2 14 2h38"/>
                    <path d="M10 52V20c0-6 4-10 10-10h32" opacity=".5"/>
                    <path d="M20 30c6-2 8-4 10-10" opacity=".7"/>
                </svg>

                <div class="cover__medallion" aria-hidden="true">{{ $initial !== '' ? $initial : '✦' }}</div>

                <p class="cover__label">{{ $cover['title'] ?? 'Undangan Pernikahan' }}</p>

                <h2 class="cover__names">
                    @forelse ($nicknames as $nickname)
                        {{ $nickname }}@if (! $loop->last) <span class="amp">&amp;</span>@endif
                    @empty
                        {{ $invitation->coupleHeadline() ?: 'Mempelai' }}
                    @endforelse
                </h2>

                <div class="rule" aria-hidden="true"><span class="rule__lozenge"></span></div>

                @if ($date !== null)
                    <p class="cover__date">{{ $date->copy()->locale('id')->translatedFormat('d F Y') }}</p>
                @endif

                <p class="cover__to">Kepada Yth.</p>
                <p class="cover__guest">{{ $recipient ?? 'Bapak/Ibu/Saudara/i' }}</p>

                <label class="cover__open" for="velvet-cover">Buka Undangan</label>
            </div>
        </div>

        @include('invitations.partials.demo-bar')
        @include('invitations.partials.showcase-bar')
        @include('invitations.partials.personal-bar')
        @if ($preview ?? false)
            <div class="preview-banner">Pratinjau draf — halaman ini belum diterbitkan dan tidak dapat diakses tamu.</div>
        @endif

        <section class="lead reveal-on-scroll" aria-label="Pembuka">
            <p class="lead__eyebrow">{{ $cover['title'] ?? 'The Wedding Of' }}</p>

            <h1 class="names">
                @forelse ($nicknames as $nickname)
                    {{ $nickname }}@if (! $loop->last) <span class="amp">&amp;</span>@endif
                @empty
                    {{ $invitation->coupleHeadline() ?: 'Mempelai' }}
                @endforelse
            </h1>

            @include('invitations.partials.photo', ['src' => $heroImage, 'class' => 'hero-arch', 'alt' => 'Foto mempelai', 'initial' => $monogram])
            <div class="rule" aria-hidden="true"><span class="rule__lozenge"></span></div>

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
                    <div class="rule" aria-hidden="true"><span class="rule__lozenge"></span></div>
                </div>

                <div class="couple-panels">
                    @foreach ($couple as $person)
                        <div class="panel">
                            <div class="person">
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
                    <div class="rule" aria-hidden="true"><span class="rule__lozenge"></span></div>
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

        
        @include('invitations.partials.gallery', ['galleryImages' => $galleryImages])

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
