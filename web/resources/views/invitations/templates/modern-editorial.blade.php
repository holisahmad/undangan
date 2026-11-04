@php
    $cover = $invitation->content['cover'] ?? [];
    $story = $invitation->content['story'] ?? null;
    $features = $invitation->content['features'] ?? [];
    $couple = $invitation->couple;
    $events = $invitation->events;
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
<html lang="id" data-template="modern-editorial.v1">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $invitation->coupleHeadline() ?: 'Undangan Pernikahan' }}</title>
    @include('invitations.partials.head-meta')
    <style>
        :root {
            --paper: #ffffff;
            --ink: #171717;
            --muted: #6b7280;
            --accent: #a4402a;
            --line: #e5e5e5;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--paper);
            color: var(--ink);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Inter, Roboto, sans-serif;
            line-height: 1.65;
            -webkit-font-smoothing: antialiased;
        }
        .preview-banner {
            background: var(--ink);
            color: #fff;
            font-size: .78rem;
            letter-spacing: .04em;
            padding: 10px 16px;
            text-align: center;
        }
        .page { margin: 0 auto; max-width: 46rem; padding: 56px 24px 40px; }
        .eyebrow {
            margin: 0;
            color: var(--accent);
            font-size: .72rem;
            font-weight: 600;
            letter-spacing: .32em;
            text-transform: uppercase;
        }
        .greeting { margin: 24px 0 0; color: var(--muted); font-size: .95rem; }
        .names {
            margin: 12px 0 0;
            font-size: clamp(2rem, 7vw, 3.25rem);
            font-weight: 300;
            letter-spacing: -.02em;
            line-height: 1.1;
        }
        .rule { margin: 36px 0; border: 0; border-top: 1px solid var(--line); }
        .quote {
            margin: 32px 0 0;
            padding: 0 0 0 18px;
            border-left: 2px solid var(--accent);
            color: var(--muted);
            font-size: .95rem;
        }
        .quote cite { display: block; margin-top: 8px; font-size: .8rem; font-style: normal; letter-spacing: .08em; text-transform: uppercase; }
        section { margin-top: 44px; }
        h2 {
            margin: 0 0 20px;
            font-size: .72rem;
            font-weight: 600;
            letter-spacing: .28em;
            text-transform: uppercase;
            color: var(--muted);
        }
        .person { padding: 14px 0; border-top: 1px solid var(--line); }
        .person:last-child { border-bottom: 1px solid var(--line); }
        .role { margin: 0; font-size: .7rem; letter-spacing: .2em; text-transform: uppercase; color: var(--accent); }
        .name { margin: 6px 0 0; font-size: 1.35rem; font-weight: 500; letter-spacing: -.01em; }
        .meta { margin: 6px 0 0; color: var(--muted); font-size: .9rem; }
        .story p { margin: 0; color: #333; }
        .event { padding: 16px 0; border-top: 1px solid var(--line); }
        .event:last-child { border-bottom: 1px solid var(--line); }
        .when { margin: 0 0 4px; font-size: .9rem; letter-spacing: .04em; text-transform: uppercase; color: var(--ink); }
        .venue { margin: 0; font-size: 1rem; font-weight: 500; }
        .address { margin: 4px 0 0; color: var(--muted); font-size: .9rem; }
        .map-link { display: inline-block; margin-top: 10px; color: var(--accent); font-size: .85rem; text-decoration: none; border-bottom: 1px solid currentColor; }
        footer { margin-top: 56px; padding-top: 16px; border-top: 1px solid var(--line); color: var(--muted); font-size: .75rem; letter-spacing: .14em; text-transform: uppercase; }
        a { color: inherit; }
        /* Phones get tighter margins; long headlines and addresses wrap. */
        @media (max-width: 480px) {
            .page { padding: 32px 18px 36px; }
            .name, .venue, .address { overflow-wrap: anywhere; }
        }

</style>
</head>
<body>
    @include('invitations.partials.demo-bar')
    @include('invitations.partials.showcase-bar')
    @include('invitations.partials.personal-bar')
    @if ($preview)
        <div class="preview-banner">Pratinjau draf — halaman ini belum diterbitkan dan tidak dapat diakses tamu.</div>
    @endif

    <main class="page">
        <p class="eyebrow">{{ $cover['title'] ?? 'The Wedding Of' }}</p>

        @if (! empty($cover['greeting']))
            <p class="greeting">{{ $cover['greeting'] }}</p>
        @endif

        @include('invitations.partials.photo', ['src' => $heroImage, 'class' => 'hero-arch', 'alt' => 'Foto mempelai', 'initial' => $monogram])
        <h1 class="names">{{ $invitation->coupleHeadline() ?: 'Mempelai' }}</h1>
        @if ($recipient !== null)
            <div class="personal-badge" style="text-align: center; margin: 18px auto; max-width: 20rem; padding: 12px 16px; border: 1px dashed rgba(169, 139, 95, .4); border-radius: 8px; background: rgba(255, 255, 255, .7);">
                <p style="margin: 0; font-size: .62rem; text-transform: uppercase; letter-spacing: .22em; opacity: .75;">Kepada Yth. Bapak/Ibu/Saudara/i:</p>
                <p style="margin: 4px 0 0; font-size: 1.15rem; font-weight: 500;">{{ $recipient }}</p>
            </div>
        @endif


        @if (! empty($cover['quote']))
            <blockquote class="quote">
                {{ $cover['quote'] }}
                @if (! empty($cover['quote_source']))
                    <cite>{{ $cover['quote_source'] }}</cite>
                @endif
            </blockquote>
        @endif

        <hr class="rule">

        @if ($couple !== [])
            <section id="mempelai" aria-label="Mempelai">
                <h2>Mempelai</h2>
                @foreach ($couple as $person)
                    <div class="person">
                        @php
                            $isBride = ($person['type'] ?? '') === 'bride';
                            $photo = $isBride ? $brideImage : $groomImage;
                        @endphp
                        @include('invitations.partials.photo', ['src' => $photo, 'class' => 'person-arch', 'alt' => $person['display_name'] ?? 'Mempelai', 'initial' => $monogram, 'size' => ['width' => 300, 'height' => 400]])
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
            <section class="story" aria-label="Cerita">
                <h2>{{ $story['heading'] ?? 'Cerita Kami' }}</h2>
                <p>{!! nl2br(e($story['body'])) !!}</p>
            </section>
        @endif

        @if (($features['show_events'] ?? true) && $events !== [])
            <section id="acara" aria-label="Acara">
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

        
        @include('invitations.partials.gallery', ['galleryImages' => $galleryImages])

        @include('invitations.partials.power')

        @include('invitations.partials.extras')

        <footer>{{ $invitation->templateName }}</footer>
    </main>
</body>
</html>
