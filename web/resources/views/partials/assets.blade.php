@include('partials.brand-head')

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300..800;1,9..40,300..800&family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Plus+Jakarta+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">

@if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite(['resources/css/app.css', 'resources/js/app.js'])
@else
    {{-- Rendered before `npm run build`; layouts stay readable, styling is minimal. --}}
    <style>
        body { margin: 0; font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif; background: #fafaf9; color: #1c1917; }
        .font-serif { font-family: 'Playfair Display', Georgia, serif; }
        main { padding: 1.5rem; }
        a { color: #9f1239; }
    </style>
@endif
