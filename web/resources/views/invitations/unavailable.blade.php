@php
    $messages = [
        'expired' => [
            'title' => 'Masa aktif undangan telah berakhir',
            'body' => 'Tautan undangan ini sudah tidak aktif. Silakan hubungi pemilik undangan untuk informasi lebih lanjut.',
        ],
        'unpublished' => [
            'title' => 'Undangan sedang tidak ditampilkan',
            'body' => 'Pemilik undangan menonaktifkan tautan ini untuk sementara.',
        ],
        'unavailable' => [
            'title' => 'Undangan belum dapat ditampilkan',
            'body' => 'Halaman undangan ini sedang tidak tersedia. Silakan coba lagi nanti.',
        ],
    ];
    $message = $messages[$reason] ?? $messages['unavailable'];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $message['title'] }}</title>
    <meta name="robots" content="noindex">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: #fbf8f1;
            color: #3a352d;
            font-family: "Iowan Old Style", "Palatino Linotype", Palatino, Georgia, serif;
            padding: 24px;
        }
        .card { max-width: 26rem; text-align: center; }
        h1 { font-size: 1.4rem; font-weight: 400; margin: 0 0 12px; }
        p { margin: 0; color: #7a7264; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ $message['title'] }}</h1>
        <p>{{ $message['body'] }}</p>
    </div>
</body>
</html>
