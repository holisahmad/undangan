@php
    $cover = $invitation->content['cover'] ?? [];
    $story = $invitation->content['story'] ?? null;
    $features = $invitation->content['features'] ?? [];
    $couple = $invitation->couple;
    $events = $invitation->events;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $invitation->coupleHeadline() ?: 'Undangan Pernikahan' }}</title>
    <meta name="robots" content="noindex">
    <style>
        :root {
            --ivory: #fbf8f1;
            --ink: #3a352d;
            --muted: #7a7264;
            --gold: #a98b5f;
            --line: #e4dbc9;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--ivory);
            color: var(--ink);
            font-family: "Iowan Old Style", "Palatino Linotype", Palatino, Georgia, serif;
            line-height: 1.6;
        }
        .preview-banner {
            background: #3a352d;
            color: #f7f2e7;
            font-family: ui-sans-serif, system-ui, sans-serif;
            font-size: 13px;
            letter-spacing: .02em;
            padding: 10px 16px;
            text-align: center;
        }
        .page { max-width: 640px; margin: 0 auto; padding: 48px 24px 64px; }
        .divider { display: flex; align-items: center; gap: 12px; margin: 32px 0; color: var(--gold); }
        .divider::before, .divider::after { content: ""; flex: 1; height: 1px; background: var(--line); }
        .eyebrow { text-transform: uppercase; letter-spacing: .28em; font-size: 11px; color: var(--muted); text-align: center; }
        h1 { font-size: clamp(2rem, 9vw, 3rem); font-weight: 400; text-align: center; margin: 8px 0 0; }
        h2 { font-size: 1.5rem; font-weight: 400; margin: 0 0 8px; }
        .names { font-size: clamp(1.6rem, 7vw, 2.4rem); text-align: center; margin: 24px 0 8px; }
        .greeting { text-align: center; color: var(--muted); margin: 0 0 8px; }
        .quote { margin: 28px auto 0; max-width: 34rem; text-align: center; font-style: italic; color: var(--muted); }
        .quote cite { display: block; margin-top: 8px; font-style: normal; font-size: .85rem; letter-spacing: .08em; text-transform: uppercase; }
        .person { padding: 20px 0; text-align: center; }
        .person .role { font-size: .75rem; text-transform: uppercase; letter-spacing: .2em; color: var(--gold); }
        .person .name { font-size: 1.4rem; margin: 6px 0; }
        .person .meta { color: var(--muted); font-size: .95rem; }
        .amp { text-align: center; color: var(--gold); font-size: 1.6rem; }
        .story p { color: #4b4438; }
        .event { border: 1px solid var(--line); padding: 20px; margin-top: 16px; background: #fffdf8; }
        .event .when { color: var(--muted); font-size: .95rem; }
        .event .venue { margin-top: 8px; font-size: 1.05rem; }
        .event .address { color: var(--muted); font-size: .95rem; }
        .map-link { display: inline-block; margin-top: 10px; font-size: .9rem; color: #7c5f33; }
        footer { margin-top: 48px; text-align: center; color: var(--muted); font-size: .8rem; }
        a { color: inherit; }
    </style>
</head>
<body>
    @if ($preview)
        <div class="preview-banner">Pratinjau draf — halaman ini belum diterbitkan dan tidak dapat diakses tamu.</div>
    @endif

    <main class="page">
        <p class="eyebrow">{{ $cover['title'] ?? 'The Wedding Of' }}</p>

        @if (! empty($cover['greeting']))
            <p class="greeting">{{ $cover['greeting'] }}</p>
        @endif

        <h1 class="names">{{ $invitation->coupleHeadline() ?: 'Mempelai' }}</h1>

        @if (! empty($cover['quote']))
            <blockquote class="quote">
                &ldquo;{{ $cover['quote'] }}&rdquo;
                @if (! empty($cover['quote_source']))
                    <cite>{{ $cover['quote_source'] }}</cite>
                @endif
            </blockquote>
        @endif

        <div class="divider">&diams;</div>

        @if ($couple !== [])
            <section aria-label="Mempelai">
                @foreach ($couple as $index => $person)
                    @if ($index > 0)
                        <p class="amp">&amp;</p>
                    @endif
                    <div class="person">
                        <p class="role">{{ $person['type'] === 'groom' ? 'Mempelai Pria' : ($person['type'] === 'bride' ? 'Mempelai Wanita' : 'Mempelai') }}</p>
                        <p class="name">{{ $person['display_name'] ?? '' }}</p>
                        @if (! empty($person['parents']))
                            <p class="meta">Putra/Putri dari {{ $person['parents'] }}</p>
                        @endif
                        @if (! empty($person['biography']))
                            <p class="meta">{{ $person['biography'] }}</p>
                        @endif
                    </div>
                @endforeach
            </section>
        @endif

        @if (($features['show_story'] ?? true) && ! empty($story['body']))
            <div class="divider">&diams;</div>
            <section class="story" aria-label="Cerita">
                <h2>{{ $story['heading'] ?? 'Cerita Kami' }}</h2>
                <p>{!! nl2br(e($story['body'])) !!}</p>
            </section>
        @endif

        @if (($features['show_events'] ?? true) && $events !== [])
            <div class="divider">&diams;</div>
            <section aria-label="Acara">
                <h2>Acara</h2>
                @foreach ($events as $event)
                    <article class="event">
                        <h3 class="venue">{{ $event['title'] ?? '' }}</h3>
                        @if (! empty($event['starts_label']))
                            <p class="when">
                                {{ $event['starts_label'] }}
                                @if (! empty($event['ends_label']))
                                    &ndash; {{ $event['ends_label'] }}
                                @endif
                            </p>
                        @endif
                        <p class="venue">{{ $event['venue_name'] ?? '' }}</p>
                        @if (! empty($event['address']))
                            <p class="address">{{ $event['address'] }}</p>
                        @endif
                        @if (($features['show_map'] ?? true) && ! empty($event['map_url']))
                            <a class="map-link" href="{{ $event['map_url'] }}" target="_blank" rel="noopener noreferrer">Lihat peta &rarr;</a>
                        @endif
                    </article>
                @endforeach
            </section>
        @endif

        <footer>
            <p>{{ $invitation->templateName }}</p>
        </footer>
    </main>
</body>
</html>
