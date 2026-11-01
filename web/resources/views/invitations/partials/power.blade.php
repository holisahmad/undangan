@php
    $features = $invitation->content['features'] ?? [];
    $hasWishes = ($wishes['total'] ?? 0) > 0;
@endphp

{{-- Shared "power kit": every template gets the same guest-facing blocks.
     Colours are inherited from the template through the --pw-* variables, so a
     new design only has to set those five values to look native. --}}
<style>
    .pw { margin: 2.6rem 0 0; }
    .pw-eyebrow {
        margin: 0 0 .55rem;
        font-size: .64rem;
        letter-spacing: .34em;
        text-transform: uppercase;
        text-align: center;
        opacity: .72;
    }
    .pw-title {
        margin: 0;
        font-family: var(--pw-display, inherit);
        font-size: clamp(1.45rem, 5vw, 1.95rem);
        font-weight: 400;
        line-height: 1.2;
        text-align: center;
        letter-spacing: .01em;
    }
    .pw-lede {
        margin: .55rem auto 0;
        max-width: 30rem;
        font-size: .92rem;
        line-height: 1.65;
        text-align: center;
        opacity: .78;
    }
    .pw-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .4rem;
        min-height: 42px;
        padding: .6rem 1.15rem;
        border: 1px solid var(--pw-line, rgba(0, 0, 0, .22));
        border-radius: 999px;
        background: transparent;
        color: inherit;
        font: inherit;
        font-size: .78rem;
        letter-spacing: .1em;
        text-transform: uppercase;
        text-decoration: none;
        cursor: pointer;
        transition: background-color .2s ease, border-color .2s ease;
    }
    .pw-btn:hover, .pw-btn:focus-visible { border-color: var(--pw-accent, currentColor); }
    .pw-btn--solid {
        border-color: var(--pw-accent, currentColor);
        background: var(--pw-accent, currentColor);
        color: var(--pw-accent-ink, #fff);
    }
    .pw-btn--solid:hover, .pw-btn--solid:focus-visible { opacity: .9; }
    .pw-btn[data-copied="true"] { border-color: var(--pw-accent, currentColor); }
    @media (prefers-reduced-motion: reduce) {
        .pw-btn { transition: none; }
    }
</style>

@include('invitations.partials.power-countdown')
@include('invitations.partials.power-gift')
@include('invitations.partials.power-wishes')
@include('invitations.partials.power-nav')
@include('invitations.partials.power-music')

{{-- Progressive enhancement only: the countdown is already correct in the HTML
     and every action below works without this file. --}}
<script src="{{ asset('js/invitation.js') }}" defer></script>
