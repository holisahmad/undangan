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
<html lang="id" data-template="terracotta-sunset.v1">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $invitation->coupleHeadline() ?: 'Undangan Pernikahan' }}</title>
    @include('invitations.partials.head-meta')
    <style>
        :root {
            --clay-deep: #7f3520;
            --clay: #b4552f;
            --amber: #d98e42;
            --sand: #f7ecdc;
            --sand-deep: #eedbc2;
            --card: #fffaf3;
            --ink: #33201a;
            --muted: #7e5a48;
            --serif: "Iowan Old Style", "Palatino Linotype", Palatino, Georgia, serif;
            --script: "Segoe Script", "Bradley Hand", "Snell Roundhand", "Apple Chancery", "Lucida Handwriting", cursive;
            --pw-accent: #b4552f;
            --pw-accent-ink: #fffaf3;
            --pw-line: rgba(127, 53, 32, .26);
            --pw-surface: rgba(255, 250, 243, .78);
            --pw-radius: 16px;
            --pw-display: "Iowan Old Style", Georgia, serif;
            --pw-numeric: "Iowan Old Style", Georgia, serif;
            --pw-nav-bg: rgba(255, 250, 243, .94);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: var(--ink);
            font-family: var(--serif);
            line-height: 1.72;
            background:
                radial-gradient(circle at 50% -6%, rgba(217, 142, 66, .2), transparent 52%),
                linear-gradient(180deg, var(--sand) 0%, #fdf7ef 46%, var(--sand) 100%);
            -webkit-font-smoothing: antialiased;
        }
        a { color: var(--clay); }

        /* ---- Cover gate ---------------------------------------------------
           Checkbox + label only. The evening sky is painted with gradients, so
           the first paint is a single HTML document with no image requests. */
        .pw-cover-switch { position: absolute; width: 1px; height: 1px; margin: 0; opacity: 0; pointer-events: none; }
        .pw-cover-switch:focus-visible ~ .cover .cover__open { outline: 2px solid #fff3df; outline-offset: 6px; }
        .cover {
            position: fixed;
            inset: 0;
            z-index: 60;
            display: grid;
            place-items: center;
            padding: 20px;
            overflow-y: auto;
            background:
                radial-gradient(circle at 50% 104%, rgba(255, 236, 205, .92) 0 96px, rgba(255, 236, 205, 0) 98px),
                linear-gradient(180deg, #6f2d1a 0%, #a94b2a 32%, #cf7a3c 66%, #e9b273 100%);
            transition: opacity .6s ease, visibility .6s ease;
        }
        .pw-cover-switch:checked ~ .cover { opacity: 0; visibility: hidden; pointer-events: none; }
        body:has(.pw-cover-switch:not(:checked)) { overflow: hidden; }

        .cover__arch {
            position: relative;
            width: min(100%, 22rem);
            padding: 2.6rem 1.6rem 2.4rem;
            border: 1px solid rgba(255, 243, 223, .55);
            border-radius: 190px 190px 10px 10px;
            background: linear-gradient(180deg, rgba(255, 243, 223, .12), rgba(127, 53, 32, .26));
            text-align: center;
            color: #fff6e7;
        }
        .cover__arch::before {
            content: "";
            position: absolute;
            inset: 9px;
            border: 1px solid rgba(255, 243, 223, .26);
            border-radius: 182px 182px 6px 6px;
            pointer-events: none;
        }
        .cover__sun { display: block; width: 60px; margin: 0 auto .6rem; color: #ffeccd; }
        .cover__label {
            margin: 0;
            font-size: .58rem;
            letter-spacing: .46em;
            text-transform: uppercase;
            color: rgba(255, 246, 231, .82);
        }
        .cover__names {
            margin: .9rem 0 0;
            font-family: var(--script);
            font-size: clamp(2.1rem, 9vw, 2.9rem);
            font-weight: 400;
            line-height: 1.16;
        }
        .cover__names .amp {
            display: block;
            margin: .1rem 0;
            font-family: var(--serif);
            font-size: .8rem;
            letter-spacing: .32em;
            color: rgba(255, 246, 231, .8);
        }
        .cover__date {
            margin: 1rem 0 0;
            font-size: .68rem;
            letter-spacing: .32em;
            text-transform: uppercase;
            color: #ffeccd;
        }
        .cover__rule {
            width: 44px;
            height: 1px;
            margin: 1.4rem auto;
            border: 0;
            background: rgba(255, 246, 231, .55);
        }
        .cover__to { margin: 0; font-size: .58rem; letter-spacing: .28em; text-transform: uppercase; color: rgba(255, 246, 231, .7); }
        .cover__guest { margin: .4rem 0 0; font-size: 1.02rem; }
        .cover__open {
            display: inline-block;
            margin-top: 1.7rem;
            padding: .7rem 1.9rem;
            border: 1px solid rgba(255, 243, 223, .8);
            border-radius: 999px;
            color: #fff6e7;
            font-size: .62rem;
            letter-spacing: .28em;
            text-transform: uppercase;
            cursor: pointer;
            transition: background-color .25s ease, color .25s ease;
        }
        .cover__open:hover { background: #fff3df; color: var(--clay-deep); }

        /* ---- Page ---- */
        .preview-banner {
            padding: 10px 16px;
            background: var(--clay-deep);
            color: #fff6e7;
            font-family: ui-sans-serif, system-ui, sans-serif;
            font-size: 13px;
            text-align: center;
        }
        .page { max-width: 600px; margin: 0 auto; padding: 0 20px 66px; }
        .lead { padding: 54px 0 0; text-align: center; }
        .lead__rule {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .6rem;
            margin: 0 0 1.1rem;
            color: var(--amber);
            font-size: .62rem;
            letter-spacing: .4em;
        }
        .lead__rule::before,
        .lead__rule::after {
            content: "";
            width: 34px;
            height: 1px;
            background: currentColor;
            opacity: .55;
        }
        .lead__date {
            margin: 1.15rem 0 0;
            font-size: .68rem;
            letter-spacing: .3em;
            text-transform: uppercase;
            color: var(--muted);
        }
        h1.names {
            margin: .75rem 0 0;
            font-family: var(--script);
            font-size: clamp(2.05rem, 8vw, 2.85rem);
            font-weight: 400;
            line-height: 1.2;
        }
        h1.names .amp {
            display: block;
            margin: .12rem 0;
            font-family: var(--serif);
            font-size: .78rem;
            letter-spacing: .34em;
            color: var(--clay);
        }
        .greeting { margin: 1.25rem auto 0; max-width: 27rem; font-size: .94rem; color: var(--muted); }
        .quote {
            margin: 2.2rem auto 0;
            max-width: 27rem;
            padding: 1.4rem 1.2rem;
            border-top: 1px solid rgba(127, 53, 32, .18);
            border-bottom: 1px solid rgba(127, 53, 32, .18);
            font-style: italic;
            color: var(--muted);
        }
        .quote cite {
            display: block;
            margin-top: .6rem;
            font-style: normal;
            font-size: .6rem;
            letter-spacing: .24em;
            text-transform: uppercase;
            color: var(--clay);
        }

        .block { margin-top: 2.6rem; }
        .block__head { display: flex; align-items: center; gap: .7rem; margin-bottom: 1.3rem; }
        .block__head::after { content: ""; flex: 1; height: 1px; background: rgba(127, 53, 32, .16); }
        .block__title {
            margin: 0;
            padding: .32rem .85rem;
            border: 1px solid rgba(127, 53, 32, .22);
            border-radius: 999px;
            font-size: .6rem;
            letter-spacing: .3em;
            text-transform: uppercase;
            color: var(--clay);
        }
        .card {
            padding: 1.8rem 1.35rem;
            border: 1px solid rgba(127, 53, 32, .14);
            border-radius: 18px;
            background: var(--card);
            box-shadow: 0 16px 34px rgba(127, 53, 32, .07);
        }
        .card + .card { margin-top: 1rem; }
        .person { text-align: center; }
        .person + .person { margin-top: 1.7rem; }
        .person__role { margin: 0; font-size: .58rem; letter-spacing: .3em; text-transform: uppercase; color: var(--amber); }
        .person__name { margin: .4rem 0 0; font-family: var(--script); font-size: clamp(1.6rem, 6vw, 2.05rem); font-weight: 400; line-height: 1.3; }
        .person__meta { margin: .5rem auto 0; max-width: 23rem; font-size: .85rem; color: var(--muted); }
        .amp-mark { margin: 1.4rem 0; text-align: center; font-size: .95rem; letter-spacing: .3em; color: var(--amber); }
        .story__text { margin: 0; font-size: .95rem; color: #4b3128; }
        .story .person__role { margin-bottom: .6rem; }
        .event__day { margin: 0; font-size: .58rem; letter-spacing: .3em; text-transform: uppercase; color: var(--clay); }
        .event__title { margin: .45rem 0 0; font-family: var(--script); font-size: 1.45rem; font-weight: 400; }
        .event__when { margin: .3rem 0 0; font-size: .9rem; }
        .event__venue { margin: .35rem 0 0; font-size: .96rem; }
        .event__address { margin: .12rem 0 0; font-size: .85rem; color: var(--muted); }
        .event__map {
            display: inline-block;
            margin-top: .7rem;
            padding-bottom: 2px;
            border-bottom: 1px solid rgba(180, 85, 47, .45);
            font-size: .64rem;
            letter-spacing: .22em;
            text-transform: uppercase;
            text-decoration: none;
            color: var(--clay);
        }
        footer { margin-top: 3rem; text-align: center; color: var(--muted); }
        footer .sun { display: block; width: 40px; margin: 0 auto .8rem; color: var(--amber); }
        footer p { margin: 0; font-size: .6rem; letter-spacing: .28em; text-transform: uppercase; }

        @media (min-width: 640px) {
            .couple-grid { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 1.1rem; }
            .couple-grid .amp-mark { margin: 0; }
            .person + .person { margin-top: 0; }
            .card { padding: 2.1rem 1.9rem; }
        }
        @media (max-width: 420px) {
            .page { padding: 0 16px 58px; }
            .card { padding: 1.5rem 1.1rem; }
            .person__name, .event__venue, .person__meta { overflow-wrap: anywhere; }
        }
        @media (prefers-reduced-motion: reduce) {
            .cover, .cover__open { transition: none; }
        }
        @media print {
            .cover, .pw-cover-switch { display: none !important; }
            body { background: #fff; }
            .card { box-shadow: none; }
        }

</style>
</head>
<body>
    <main class="page">
        <input class="pw-cover-switch" type="checkbox" id="terracotta-cover" aria-label="Buka undangan">
        <div class="cover">
            <div class="cover__arch">
                @include('invitations.partials.terracotta-sun', ['class' => 'cover__sun'])
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

                <label class="cover__open" for="terracotta-cover">Buka Undangan</label>
            </div>
        </div>

        @include('invitations.partials.demo-bar')
        @include('invitations.partials.showcase-bar')
        @include('invitations.partials.personal-bar')
        @if ($preview ?? false)
            <div class="preview-banner">Pratinjau draf — halaman ini belum diterbitkan dan tidak dapat diakses tamu.</div>
        @endif

        @include('invitations.partials.photo', ['src' => $heroImage, 'class' => 'hero-arch', 'alt' => 'Foto mempelai', 'initial' => $monogram])
        <section class="lead" aria-label="Pembuka">
            <p class="lead__rule">{{ $cover['title'] ?? 'The Wedding Of' }}</p>

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
                <div class="block__head"><h2 class="block__title">Mempelai</h2></div>

                <div class="card">
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
                        @include('invitations.partials.photo', ['src' => $photo, 'class' => 'person-arch', 'alt' => $person['display_name'] ?? 'Mempelai', 'initial' => $monogram, 'size' => ['width' => 300, 'height' => 400]])
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
                </div>
            </section>
        @endif

        @if (($features['show_story'] ?? true) && ! empty($story['body']))
            <section class="block story" aria-label="Cerita">
                <div class="block__head"><h2 class="block__title">Cerita</h2></div>
                <div class="card">
                    @if (! empty($story['heading']))
                        @php
                            $isBride = ($person['type'] ?? '') === 'bride';
                            $photo = $isBride ? $brideImage : $groomImage;
                        @endphp
                        <div class="person-arch">
                            <img src="{{ $photo }}" alt="{{ $person['display_name'] ?? 'Mempelai' }}" width="300" height="400">
                        </div>
                        <p class="person__role">{{ $story['heading'] }}</p>
                    @endif
                    <p class="story__text">{!! nl2br(e($story['body'])) !!}</p>
                </div>
            </section>
        @endif

        @if (($features['show_events'] ?? true) && $events !== [])
            <section class="block" id="acara" aria-label="Acara">
                <div class="block__head"><h2 class="block__title">Acara</h2></div>

                @foreach ($events as $event)
                    <article class="card event">
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
            @include('invitations.partials.terracotta-sun', ['class' => 'sun'])
            <p>{{ $invitation->templateName }}</p>
        </footer>
    </main>
</body>
</html>
