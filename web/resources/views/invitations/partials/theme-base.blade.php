{{--
    Shared invitation foundation.

    Every renderer used to copy the same 70 lines of "visual enhancements" into
    its own <style> block, which is why eleven designs shared identical frames,
    identical grey shadows and an identical pulsing button. The shared pieces
    now live here once and read the --pw-* tokens each renderer already sets, so
    a design controls its own palette and radius without duplicating layout.

    Included from `head-meta`, before the renderer's own <style>, so a template
    that wants a different treatment can still override any rule below.
--}}
<style>
    /* ---- Photos -------------------------------------------------------
       A frame that holds a real photo when the couple has one, and the same
       frame around a monogram when they do not. Nothing here fakes a photo. */
    .hero-arch,
    .person-arch {
        position: relative;
        margin-inline: auto;
        overflow: hidden;
        border: 1px solid var(--pw-line, rgba(0, 0, 0, .18));
        border-radius: var(--pw-photo-radius, 46% 46% 5px 5px / 30% 30% 5px 5px);
        background: var(--pw-surface, rgba(255, 255, 255, .45));
        box-shadow: 0 24px 50px -38px rgba(0, 0, 0, .65);
        isolation: isolate;
    }
    .hero-arch {
        width: min(100%, 15.5rem);
        aspect-ratio: 4 / 5;
        margin-block: 1.5rem 1.35rem;
    }
    .person-arch {
        width: min(100%, 9.5rem);
        aspect-ratio: 3 / 4;
        margin-bottom: 1rem;
    }
    .hero-arch img,
    .person-arch img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform .9s cubic-bezier(.16, 1, .3, 1);
    }
    .hero-arch:hover img,
    .person-arch:hover img {
        transform: scale(1.035);
    }

    .inv-monogram {
        display: grid;
        place-items: center;
        gap: .3rem;
        text-align: center;
    }
    .inv-monogram span {
        font-family: var(--pw-display, Georgia, serif);
        font-size: clamp(1.35rem, 6.5vw, 2rem);
        line-height: 1;
        letter-spacing: .14em;
        color: var(--pw-accent, currentColor);
        opacity: .92;
    }
    .inv-monogram::after {
        content: "";
        display: block;
        width: 22px;
        height: 1px;
        background: currentColor;
        opacity: .28;
        margin-top: .1rem;
    }

    /* ---- Gallery ------------------------------------------------------
       Only rendered when the invitation actually has photos. */
    .inv-gallery {
        margin-top: 2.8rem;
        text-align: center;
    }
    .inv-gallery__eyebrow {
        margin: 0;
        font-family: var(--pw-numeric, ui-sans-serif, system-ui, sans-serif);
        font-size: .58rem;
        letter-spacing: .34em;
        text-transform: uppercase;
        opacity: .7;
    }
    .inv-gallery__title {
        margin: .45rem 0 1.35rem;
        font-family: var(--pw-display, Georgia, serif);
        font-size: clamp(1.2rem, 4.4vw, 1.55rem);
        font-weight: 400;
        letter-spacing: .04em;
        line-height: 1.3;
    }
    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .55rem;
    }
    .gallery-item {
        position: relative;
        margin: 0;
        aspect-ratio: 1;
        overflow: hidden;
        border-radius: var(--pw-radius, 6px);
        border: 1px solid var(--pw-line, rgba(0, 0, 0, .14));
    }
    .gallery-item:first-child {
        grid-column: span 2;
        aspect-ratio: 16 / 10;
    }
    .gallery-item img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform .9s cubic-bezier(.16, 1, .3, 1);
    }
    .gallery-item:hover img {
        transform: scale(1.04);
    }

    /* ---- Reveal on scroll ---------------------------------------------
       Gated behind `html.js`, which invitation.js sets. Without JavaScript the
       content is simply visible instead of stuck at opacity 0. */
    .js .reveal-on-scroll {
        opacity: 0;
        transform: translateY(22px);
        transition: opacity .8s cubic-bezier(.16, 1, .3, 1), transform .8s cubic-bezier(.16, 1, .3, 1);
    }
    .js .reveal-on-scroll.is-revealed {
        opacity: 1;
        transform: none;
    }

    /* ---- RSVP --------------------------------------------------------
       Inherits the invitation's palette and type instead of stamping a
       burgundy-and-white form onto every design. */
    .inv-rsvp {
        margin-top: 2.8rem;
        padding: 1.5rem 1.25rem;
        border: 1px solid var(--pw-line, rgba(0, 0, 0, .16));
        border-radius: var(--pw-radius, 10px);
        background: var(--pw-surface, transparent);
        color: inherit;
        font-family: var(--pw-numeric, ui-sans-serif, system-ui, sans-serif);
        text-align: left;
    }
    .inv-rsvp h2 {
        margin: 0 0 .4rem;
        font-family: var(--pw-display, Georgia, serif);
        font-size: 1.2rem;
        font-weight: 400;
        letter-spacing: .03em;
    }
    .inv-rsvp .inv-meta {
        margin: 0 0 1.1rem;
        font-size: .82rem;
        line-height: 1.6;
        opacity: .78;
    }
    .inv-option {
        display: flex;
        align-items: center;
        gap: .55rem;
        padding: .45rem 0;
        font-size: .92rem;
        accent-color: var(--pw-accent, currentColor);
    }
    .inv-field {
        margin-top: .85rem;
    }
    .inv-field label {
        display: block;
        margin-bottom: .3rem;
        font-size: .78rem;
        letter-spacing: .02em;
        opacity: .85;
    }
    .inv-field select,
    .inv-field textarea {
        width: 100%;
        padding: .6rem .7rem;
        border: 1px solid var(--pw-line, rgba(0, 0, 0, .2));
        border-radius: calc(var(--pw-radius, 10px) / 1.6);
        background: rgba(255, 255, 255, .1);
        color: inherit;
        font: inherit;
        font-size: .9rem;
    }
    .inv-field select option {
        color: #1c1917;
    }
    .inv-field textarea {
        min-height: 5.2rem;
        resize: vertical;
    }
    .inv-consent {
        display: flex;
        align-items: flex-start;
        gap: .5rem;
        margin-top: .85rem;
        font-size: .76rem;
        line-height: 1.55;
        opacity: .85;
    }
    .inv-consent input {
        margin-top: .15rem;
        accent-color: var(--pw-accent, currentColor);
    }
    .inv-rsvp button {
        width: 100%;
        margin-top: 1.1rem;
        padding: .7rem 1rem;
        border: 1px solid var(--pw-accent, currentColor);
        border-radius: 999px;
        background: var(--pw-accent, currentColor);
        color: var(--pw-accent-ink, #fff);
        font: inherit;
        font-size: .8rem;
        font-weight: 600;
        letter-spacing: .12em;
        text-transform: uppercase;
        cursor: pointer;
        transition: opacity .2s ease;
    }
    .inv-rsvp button:hover {
        opacity: .88;
    }
    .inv-status,
    .inv-errors {
        margin: 0 0 .9rem;
        padding: .65rem .8rem;
        border: 1px solid var(--pw-line, rgba(0, 0, 0, .2));
        border-radius: calc(var(--pw-radius, 10px) / 1.6);
        font-size: .8rem;
        line-height: 1.55;
        background: rgba(255, 255, 255, .12);
    }
    .inv-errors ul {
        margin: .35rem 0 0;
        padding-left: 1.1rem;
    }
    .inv-gallery-link {
        margin: 1.75rem 0 0;
        text-align: center;
    }
    .inv-gallery-link a {
        display: inline-block;
        padding: .55rem 1.2rem;
        border: 1px solid currentColor;
        border-radius: 999px;
        font-family: var(--pw-numeric, ui-sans-serif, system-ui, sans-serif);
        font-size: .72rem;
        letter-spacing: .14em;
        text-transform: uppercase;
        text-decoration: none;
    }

    @media (prefers-reduced-motion: reduce) {
        .js .reveal-on-scroll,
        .js .reveal-on-scroll.is-revealed {
            opacity: 1;
            transform: none;
            transition: none;
        }
        .hero-arch img,
        .person-arch img,
        .gallery-item img {
            transition: none;
        }
    }
</style>
