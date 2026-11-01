@if ($demo ?? false)
    <div class="inv-demo-bar" role="note">
        <span>Contoh template <strong>{{ $invitation->templateName }}</strong> dengan data fiktif.</span>
        <a href="{{ auth()->check() ? route('weddings.create') : route('register') }}">Buat undangan seperti ini &rarr;</a>
    </div>
    <style>
        .inv-demo-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 4px 12px;
            align-items: center;
            justify-content: center;
            padding: 10px 16px;
            background: #9f1239;
            color: #fff;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            font-size: 12px;
            letter-spacing: .02em;
            text-align: center;
        }
        .inv-demo-bar strong { font-weight: 600; }
        .inv-demo-bar a {
            color: #fff;
            font-weight: 600;
            text-decoration: underline;
            text-underline-offset: 3px;
        }
    </style>
@endif
