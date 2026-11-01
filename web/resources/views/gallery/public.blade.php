<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Galeri {{ $invitation->coupleHeadline() }}</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: ui-serif, Georgia, 'Times New Roman', serif;
            background: #faf7f2;
            color: #322a24;
            line-height: 1.6;
        }
        header {
            padding: 3rem 1.5rem 2rem;
            text-align: center;
            background: linear-gradient(180deg, #f3ece2 0%, #faf7f2 100%);
        }
        header p.eyebrow {
            margin: 0;
            font-family: ui-sans-serif, system-ui, sans-serif;
            font-size: .7rem;
            letter-spacing: .2em;
            text-transform: uppercase;
            color: #8a7663;
        }
        header h1 { margin: .5rem 0 0; font-size: clamp(1.6rem, 5vw, 2.4rem); font-weight: 600; }
        main { max-width: 64rem; margin: 0 auto; padding: 1rem 1.5rem 4rem; }
        section { margin-top: 2.5rem; }
        section h2 {
            font-size: 1.1rem;
            margin: 0 0 .75rem;
            display: flex;
            align-items: baseline;
            gap: .5rem;
        }
        section h2 span {
            font-family: ui-sans-serif, system-ui, sans-serif;
            font-size: .7rem;
            color: #8a7663;
            font-weight: 400;
        }
        .grid { display: grid; gap: .75rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        @media (min-width: 640px) { .grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @media (min-width: 960px) { .grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
        figure { margin: 0; background: #fff; border: 1px solid #e6dccd; border-radius: .5rem; overflow: hidden; }
        figure a { display: block; }
        figure img { display: block; width: 100%; height: 11rem; object-fit: cover; }
        figcaption {
            font-family: ui-sans-serif, system-ui, sans-serif;
            font-size: .7rem;
            color: #8a7663;
            padding: .5rem .65rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .empty {
            border: 1px dashed #d9cbb6;
            border-radius: .5rem;
            padding: 2.5rem 1.5rem;
            text-align: center;
            color: #8a7663;
            background: #fff;
        }
        footer {
            max-width: 64rem;
            margin: 0 auto;
            padding: 0 1.5rem 3rem;
            font-family: ui-sans-serif, system-ui, sans-serif;
            font-size: .7rem;
            color: #8a7663;
        }
    </style>
</head>
<body>
    <header>
        <p class="eyebrow">Galeri</p>
        <h1>{{ $invitation->coupleHeadline() }}</h1>
    </header>

    <main>
        @forelse ($albums as $group)
            <section>
                <h2>
                    {{ $group['album']->title }}
                    <span>{{ $group['assets']->count() }} foto</span>
                </h2>
                <div class="grid">
                    @foreach ($group['assets'] as $asset)
                        <figure>
                            <a href="{{ $previewUrls[$asset->id] }}" target="_blank" rel="noopener">
                                <img src="{{ $thumbUrls[$asset->id] }}"
                                     srcset="{{ $thumbUrls[$asset->id] }} 400w, {{ $previewUrls[$asset->id] }} 1280w"
                                     sizes="(min-width: 1024px) 25vw, (min-width: 640px) 50vw, 100vw"
                                     alt="Foto {{ $group['album']->title }}" loading="lazy" decoding="async">
                            </a>
                            <figcaption>{{ $asset->original_filename }}</figcaption>
                        </figure>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="empty">
                Galeri belum dibuka untuk tamu. Silakan cek kembali nanti.
            </div>
        @endforelse
    </main>

    <footer>
        Hanya album yang dibuka pemilik undangan yang tampil di halaman ini. Tautan foto berlaku sementara dan tidak dapat ditebak.
    </footer>
</body>
</html>
