@if ($showcase ?? false)
    {{-- The persistent bar is the watermark: a portfolio page always says that
         it is a portfolio page, so it can never be mistaken for a direct
         invitation link. --}}
    <div class="inv-showcase-bar" role="note">
        <span>Portofolio publik &middot; ditampilkan atas izin mempelai.</span>
        <a href="{{ route('showcase.index') }}">Inspirasi lain</a>
        <a href="{{ auth()->check() ? route('weddings.create') : route('register') }}">Buat undangan serupa &rarr;</a>
    </div>
    <style>
        .inv-showcase-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 4px 14px;
            align-items: center;
            justify-content: center;
            padding: 10px 16px;
            background: #1c1917;
            color: #f5f5f4;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            font-size: 12px;
            letter-spacing: .02em;
            text-align: center;
        }
        .inv-showcase-bar a {
            color: #f5f5f4;
            font-weight: 600;
            text-decoration: underline;
            text-underline-offset: 3px;
        }
    </style>
@endif
