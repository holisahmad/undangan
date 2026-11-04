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
<html lang="id" data-template="garden-botanical.v1">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $invitation->coupleHeadline() ?: 'Undangan Pernikahan' }}</title>
    @include('invitations.partials.head-meta')
    <style>
        :root {
            --cream: #f7f6ef;
            --leaf: #3d5a45;
            --leaf-soft: #7d9478;
            --sand: #c9b79c;
            --ink: #33382f;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--cream);
            color: var(--ink);
            font-family: Georgia, "Iowan Old Style", "Palatino Linotype", serif;
            line-height: 1.7;
        }
        .preview-banner {
            background: var(--leaf);
            color: var(--cream);
            font-size: .78rem;
            letter-spacing: .03em;
            padding: 10px 16px;
            text-align: center;
            font-family: -apple-system, "Segoe UI", sans-serif;
        }
        .page { margin: 0 auto; max-width: 40rem; padding: 56px 24px 48px; }
        .frame {
            border: 1px solid var(--sand);
            padding: 36px 22px;
        }
        .eyebrow {
            margin: 0;
            text-align: center;
            color: var(--leaf-soft);
            font-size: .74rem;
            letter-spacing: .34em;
            text-transform: uppercase;
        }
        .greeting { margin: 22px 0 0; text-align: center; color: #5c6357; font-size: .95rem; font-style: italic; }
        .names {
            margin: 10px 0 0;
            text-align: center;
            font-size: clamp(1.9rem, 6vw, 2.75rem);
            font-weight: 400;
            line-height: 1.25;
        }
        .sprig { margin: 26px auto 0; display: block; width: 90px; text-align: center; color: var(--leaf-soft); font-size: 1.1rem; letter-spacing: .5em; }
        .quote {
            margin: 26px auto 0;
            max-width: 26rem;
            text-align: center;
            color: #5c6357;
            font-style: italic;
            font-size: .95rem;
        }
        .quote cite { display: block; margin-top: 10px; font-size: .78rem; font-style: normal; letter-spacing: .16em; text-transform: uppercase; color: var(--leaf-soft); }
        section { margin-top: 40px; }
        h2 {
            margin: 0 0 18px;
            text-align: center;
            font-size: .78rem;
            font-weight: 400;
            letter-spacing: .3em;
            text-transform: uppercase;
            color: var(--leaf);
        }
        h2::after {
            content: "";
            display: block;
            width: 46px;
            margin: 10px auto 0;
            border-top: 1px solid var(--sand);
        }
        .person { margin-top: 18px; text-align: center; }
        .role { margin: 0; font-size: .72rem; letter-spacing: .22em; text-transform: uppercase; color: var(--leaf-soft); }
        .name { margin: 6px 0 0; font-size: 1.35rem; }
        .meta { margin: 6px 0 0; color: #5c6357; font-size: .9rem; }
        .story p { margin: 0; text-align: center; }
        .event { margin-top: 22px; text-align: center; padding-top: 18px; border-top: 1px dashed var(--sand); }
        .event:first-of-type { border-top: 0; padding-top: 0; }
        .when { margin: 0 0 6px; font-size: .82rem; letter-spacing: .14em; text-transform: uppercase; color: var(--leaf); }
        .venue { margin: 0; font-size: 1.05rem; }
        .address { margin: 4px 0 0; color: #5c6357; font-size: .9rem; }
        .map-link { display: inline-block; margin-top: 10px; color: var(--leaf); font-size: .85rem; text-decoration: none; border-bottom: 1px solid var(--leaf-soft); }
        footer { margin-top: 44px; text-align: center; color: var(--leaf-soft); font-size: .74rem; letter-spacing: .2em; text-transform: uppercase; }
        a { color: inherit; }
        /* Phones get tighter margins and a lighter inner frame. */
        @media (max-width: 480px) {
            .page { padding: 32px 16px 40px; }
            .frame { padding: 26px 16px; }
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
        <div class="frame">
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

            <p class="sprig">&divide;</p>

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
                            <p class="when">
                                @if (! empty($event['starts_label']))
                                    {{ $event['starts_label'] }}
                                    @if (! empty($event['ends_label']))
                                        &ndash; {{ $event['ends_label'] }}
                                    @endif
                                @endif
                            </p>
                            <h3 class="venue">{{ $event['title'] ?? '' }}</h3>
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
        </div>
    </main>
</body>
</html>
