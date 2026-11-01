@isset($guest)
    <div class="inv-personal-bar" role="note">
        <span>Kepada <strong>{{ $guest->display_name }}</strong></span>
        <span class="inv-personal-note">Tautan pribadi &middot; mohon tidak dibagikan</span>
    </div>
    <style>
        .inv-personal-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 4px 12px;
            justify-content: center;
            padding: 8px 16px;
            background: #1c1917;
            color: #fafaf9;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            font-size: 12px;
            letter-spacing: .02em;
        }
        .inv-personal-bar strong { font-weight: 600; }
        .inv-personal-note { color: #d6d3d1; }
    </style>
@endisset
