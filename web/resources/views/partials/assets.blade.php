@if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite(['resources/css/app.css'])
@else
    {{-- Rendered before `npm run build`; layouts stay readable, styling is minimal. --}}
    <style>
        body { margin: 0; font-family: ui-sans-serif, system-ui, sans-serif; background: #fafaf9; color: #1c1917; }
        main { padding: 1.5rem; }
        a { color: #9f1239; }
    </style>
@endif
