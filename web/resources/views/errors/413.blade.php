@php
    use App\Support\Media\UploadLimits;

    // PHP aborts the request before the session starts, so this page must not
    // depend on the layout, the auth guard or Vite: just the limits and a way
    // back (docs/MEDIA_STORAGE.md, section 10).
    $limits = UploadLimits::current();
    $back = url()->previous(route('dashboard'));
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Unggahan terlalu besar &middot; {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <meta name="theme-color" content="#A6294B">
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            background: #fafaf9;
            color: #1c1917;
            font-family: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
            line-height: 1.6;
        }
        main {
            width: 100%;
            max-width: 34rem;
            padding: 24px 20px;
            background: #fff;
            border: 1px solid #e7e5e4;
            border-radius: 12px;
        }
        .eyebrow { margin: 0 0 4px; font-size: .75rem; letter-spacing: .08em; text-transform: uppercase; color: #78716c; }
        h1 { margin: 0 0 12px; font-size: 1.375rem; font-weight: 600; }
        p { margin: 0 0 12px; font-size: .95rem; color: #44403c; }
        ul { margin: 0 0 16px; padding-left: 1.15rem; font-size: .95rem; color: #44403c; }
        li { margin-bottom: 4px; }
        .limits { background: #f5f5f4; border-radius: 8px; padding: 12px 14px; }
        .limits strong { color: #1c1917; }
        .actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 20px; }
        a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 0 16px;
            border-radius: 8px;
            font-size: .95rem;
            font-weight: 500;
            text-decoration: none;
        }
        .primary { background: #9f1239; color: #fff; }
        .secondary { border: 1px solid #d6d3d1; color: #44403c; }
        @media (max-width: 480px) {
            main { padding: 20px 16px; }
            .actions a { width: 100%; }
        }
    </style>
</head>
<body>
<main>
    <p class="eyebrow">Error 413</p>
    <h1>Unggahan terlalu besar</h1>
    <p>
        Server menolak permintaan ini sebelum sempat membaca daftar fotonya, jadi belum ada foto yang tersimpan.
        Biasanya penyebabnya terlalu banyak foto dalam satu kali kirim.
    </p>
    <div class="limits">
        <p style="margin: 0 0 6px; font-weight: 600;">Batas yang berlaku sekarang</p>
        <ul style="margin: 0;">
            <li>Per foto: <strong>{{ $limits->fileMb() }} MB</strong></li>
            <li>Jumlah: <strong>{{ $limits->maxFiles() }} foto</strong> sekali unggah</li>
            <li>Total per kirim: <strong>{{ $limits->requestMb() }} MB</strong></li>
        </ul>
    </div>
    <p style="margin-top: 16px;">Yang bisa dilakukan:</p>
    <ul>
        <li>Pilih beberapa foto saja, lalu unggah lagi untuk sisanya.</li>
        <li>Perkecil foto (mis. ekspor ulang dari galeri ponsel) bila satu foto mendekati batas.</li>
        <li>Ulangi unggahan; berkas yang gagal tidak meninggalkan data setengah jadi.</li>
    </ul>
    @if ($limits->clampedByPhp())
        <p style="font-size: .85rem; color: #78716c;">
            Batas ini berasal dari konfigurasi PHP server (upload_max_filesize / post_max_size), bukan dari paket Anda.
        </p>
    @endif
    <div class="actions">
        <a class="primary" href="{{ $back }}">Kembali dan coba lagi</a>
        <a class="secondary" href="{{ route('dashboard') }}">Buka dashboard</a>
    </div>
</main>
</body>
</html>
