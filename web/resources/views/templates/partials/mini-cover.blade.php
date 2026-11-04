{{--
    A miniature of the real renderer, not a shared layout in a new colour.
    Markup only: every colour and typeface arrives as a custom property from
    TemplatePresentation, and the shapes live in resources/css/app.css.
--}}
@php
    $cover = $theme['cover'] ?? 'frame';
@endphp

<div class="mini mini--{{ $cover }}" aria-hidden="true">
    @switch($cover)
        @case('editorial')
            <div class="mini__editorial">
                <span class="mini__kicker">Undangan · 2026</span>
                <span class="mini__names mini__names--xl">Ayu<br>&amp; Bima</span>
                <span class="mini__date">12 Desember · Bandung</span>
            </div>
            @break

        @case('botanical')
            <div class="mini__frame mini__frame--botanical">
                <svg class="mini__sprig" viewBox="0 0 40 64" fill="none" aria-hidden="true">
                    <path d="M20 62V18" stroke="currentColor" stroke-width="1" opacity=".55"/>
                    <path d="M20 38c-9 0-13-6-13-13 9 0 13 6 13 13z" fill="currentColor" opacity=".3"/>
                    <path d="M20 26c9 0 13-6 13-13-9 0-13 6-13 13z" fill="currentColor" opacity=".22"/>
                    <circle cx="20" cy="14" r="4" fill="currentColor" opacity=".38"/>
                </svg>
                <span class="mini__names">Ayu &amp; Bima</span>
                <span class="mini__date">12 · 12 · 2026</span>
            </div>
            @break

        @case('gilt')
            <div class="mini__oct">
                <span class="mini__kicker">The Wedding Of</span>
                <span class="mini__names mini__names--script">Ayu &amp; Bima</span>
                <span class="mini__rule"></span>
                <span class="mini__date">12 · 12 · 2026</span>
            </div>
            @break

        @case('keepsake')
            <div class="mini__paper">
                <span class="mini__band"></span>
                <span class="mini__kicker">Mohon Doa Restu</span>
                <span class="mini__names mini__names--script">Ayu<br>&amp; Bima</span>
                <span class="mini__date">12 · 12 · 2026</span>
            </div>
            @break

        @case('heritage')
            <div class="mini__heritage">
                <svg class="mini__motif" viewBox="0 0 60 24" fill="none" aria-hidden="true">
                    <path d="M30 2 38 12H22z" stroke="currentColor" stroke-width="1"/>
                    <path d="M12 6 18 14H6zM48 6 54 14H42z" stroke="currentColor" stroke-width="1" opacity=".6"/>
                    <path d="M0 20h60" stroke="currentColor" stroke-width="1" stroke-dasharray="2 5" opacity=".5"/>
                </svg>
                <span class="mini__names">Ayu &amp; Bima</span>
                <span class="mini__date">12 · 12 · 2026</span>
            </div>
            @break

        @case('celestial')
            <div class="mini__sky">
                <span class="mini__moon"></span>
                <span class="mini__names mini__names--script">Ayu &amp; Bima</span>
                <span class="mini__date">12 · 12 · 2026</span>
            </div>
            @break

        @case('sunset')
            <div class="mini__dusk">
                <span class="mini__sun"></span>
                <span class="mini__names">Ayu &amp; Bima</span>
                <span class="mini__date">12 · 12 · 2026</span>
            </div>
            @break

        @case('window')
            <div class="mini__window">
                <span class="mini__kicker">Undangan Pernikahan</span>
                <span class="mini__names mini__names--spaced">AYU<br><span class="mini__amp">&amp;</span>BIMA</span>
                <span class="mini__date">12 · 12 · 2026</span>
            </div>
            @break

        @case('coastal')
            <div class="mini__coast">
                <span class="mini__arc mini__arc--back"></span>
                <span class="mini__arc mini__arc--front"></span>
                <span class="mini__names mini__names--script">Ayu &amp; Bima</span>
                <span class="mini__date">12 · 12 · 2026</span>
            </div>
            @break

        @case('songket')
            <div class="mini__songket">
                <span class="mini__lattice"></span>
                <span class="mini__names">Ayu &amp; Bima</span>
                <span class="mini__date">12 · 12 · 2026</span>
            </div>
            @break

        @case('royal')
            <div class="mini__royal">
                <span class="mini__monogram">A·B</span>
                <span class="mini__names">Ayu &amp; Bima</span>
                <span class="mini__date">12 · 12 · 2026</span>
            </div>
            @break

        @case('deco')
            <div class="mini__deco">
                <span class="mini__names mini__names--spaced">AYU<br><span class="mini__amp">&amp;</span>BIMA</span>
                <span class="mini__date">12 · 12 · 2026</span>
            </div>
            @break

        @default
            <div class="mini__frame">
                <span class="mini__kicker">The Wedding Of</span>
                <span class="mini__names">Ayu &amp; Bima</span>
                <span class="mini__rule"></span>
                <span class="mini__date">12 · 12 · 2026</span>
            </div>
    @endswitch
</div>
