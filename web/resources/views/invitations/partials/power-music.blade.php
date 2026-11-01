{{-- Floating Music Player for Wedding Invitations (inviee.id style) --}}
@php
    $musicUrl = $invitation->content['music_url'] ?? 'https://assets.inviee.id/Canon-in-D-Pachelbels-Canon-Cello-Piano-BEST-WEDDING-VERSION.mp3';
@endphp

<div id="wedly-music-player" class="pw-music">
    <audio id="wedly-audio" loop preload="none">
        <source src="{{ $musicUrl }}" type="audio/mp3">
    </audio>
    <button type="button" id="wedly-music-toggle" class="pw-music__btn" aria-label="Putar atau jeda musik latar" title="Musik latar">
        {{-- Vinyl Record Icon / Note Icon --}}
        <span class="pw-music__disc">
            <svg class="pw-music__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M9 18V5l12-2v13"></path>
                <circle cx="6" cy="18" r="3"></circle>
                <circle cx="18" cy="16" r="3"></circle>
            </svg>
        </span>
    </button>
</div>

<style>
    .pw-music {
        position: fixed;
        top: calc(18px + env(safe-area-inset-top, 0px));
        right: 18px;
        z-index: 55;
    }
    .pw-music__btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 42px;
        height: 42px;
        padding: 0;
        border: 1px solid var(--pw-line, rgba(255, 255, 255, .25));
        border-radius: 50%;
        background: var(--pw-nav-bg, rgba(20, 20, 25, .82));
        color: var(--pw-accent, #c9a24b);
        box-shadow: 0 4px 16px rgba(0, 0, 0, .25);
        cursor: pointer;
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        transition: transform .25s ease, background-color .2s ease;
    }
    .pw-music__btn:hover {
        transform: scale(1.08);
    }
    .pw-music__disc {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
    }
    .pw-music__icon {
        width: 20px;
        height: 20px;
    }
    .pw-music.is-playing .pw-music__disc {
        animation: pw-vinyl-spin 4s linear infinite;
    }
    @keyframes pw-vinyl-spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    /* Sembunyikan tombol musik saat cover belum dibuka */
    body:has(.pw-cover-switch:not(:checked)) .pw-music {
        display: none;
    }
    @media (prefers-reduced-motion: reduce) {
        .pw-music.is-playing .pw-music__disc {
            animation: none;
        }
    }
</style>

