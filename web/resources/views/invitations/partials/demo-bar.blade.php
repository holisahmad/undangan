{{-- Sample-page ribbon. Ink instead of brand burgundy: the bar has to sit
     above thirteen different palettes without fighting the design below it. --}}
@if ($demo ?? false)
    <div class="inv-demo-bar" role="note">
        <span>Contoh tema <strong>{{ $invitation->templateName }}</strong> · data fiktif</span>
        <a href="{{ auth()->check() ? route('weddings.create') : route('register') }}">Buat undangan seperti ini</a>
    </div>
    <style>
        .inv-demo-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 4px 14px;
            padding: 9px 16px;
            background: #17140f;
            color: #f6f1e7;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            font-size: 11px;
            letter-spacing: .08em;
            text-transform: uppercase;
            text-align: center;
        }
        .inv-demo-bar strong {
            font-weight: 600;
            letter-spacing: .12em;
        }
        .inv-demo-bar a {
            color: inherit;
            font-weight: 600;
            letter-spacing: .12em;
            text-decoration: none;
            border-bottom: 1px solid currentColor;
            padding-bottom: 1px;
        }
        .inv-demo-bar a:hover {
            opacity: .8;
        }
    </style>
@endif
