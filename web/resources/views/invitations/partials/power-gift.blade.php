@php
    $gift = $invitation->content['gift'] ?? [];
    $accounts = $invitation->giftAccounts();
    $showGift = $invitation->giftIsVisible() && (($features['show_gift'] ?? true) !== false);
@endphp

@if ($showGift)
    <section class="pw pw-gift" id="hadiah" aria-label="Hadiah pernikahan">
        <p class="pw-eyebrow">Wedding Gift</p>
        <h2 class="pw-title">Tanda Kasih</h2>

        @if (! empty($gift['note']))
            <p class="pw-lede">{{ $gift['note'] }}</p>
        @endif

        @if ($accounts !== [] || ! empty($gift['address']))
            {{-- A guest has to ask for the account numbers: they are never printed
                 on the open page. The toggle is a native <details>, so the same
                 click opens and closes it with or without JavaScript and what is
                 revealed stays plain HTML rather than something a script invents. --}}
            <details class="pw-gift__reveal" data-gift-reveal>
                <summary class="pw-btn pw-gift__toggle">
                    <span class="pw-gift__toggle-open">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px; vertical-align: -2px;" aria-hidden="true">
                            <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                            <line x1="2" y1="10" x2="22" y2="10"></line>
                        </svg>
                        Buka rekening hadiah
                    </span>
                    <span class="pw-gift__toggle-close">Sembunyikan rekening</span>
                </summary>

                <div class="pw-gift__body">
                    @if ($accounts !== [])
                        <ul class="pw-gift__list">
                            @foreach ($accounts as $account)
                                <li class="pw-gift__card">
                                    {{-- Chip and contactless mark are drawn in CSS: a
                                         real invitation never loads demo artwork. --}}
                                    <div class="pw-gift__card-chip">
                                        <span class="pw-gift__chip" aria-hidden="true"></span>
                                        <span class="pw-gift__contactless" aria-hidden="true">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M8.5 16.5a5 5 0 0 1 0-7"></path>
                                                <path d="M12 19a8.5 8.5 0 0 0 0-14"></path>
                                                <path d="M15.5 21.5a12 12 0 0 0 0-19"></path>
                                            </svg>
                                        </span>
                                    </div>

                                    <div class="pw-gift__head">
                                        <span class="pw-gift__bank">{{ $account['bank'] !== '' ? $account['bank'] : 'Rekening' }}</span>
                                    </div>

                                    <p class="pw-gift__num">{{ $account['number'] }}</p>

                                    <div class="pw-gift__footer">
                                        @if ($account['holder'] !== '')
                                            <span class="pw-gift__holder">a.n. {{ $account['holder'] }}</span>
                                        @endif

                                        {{-- The number is already on screen by the time this
                                             button can be reached, so it is a shortcut only. --}}
                                        <button type="button" class="pw-btn pw-gift__copy"
                                                data-copy="{{ $account['number'] }}"
                                                aria-label="Salin nomor rekening {{ $account['bank'] !== '' ? $account['bank'] : '' }}">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;" aria-hidden="true">
                                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                            </svg>
                                            Salin nomor
                                        </button>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if (! empty($gift['address']))
                        <div class="pw-gift__address-card">
                            <div class="pw-gift__address-icon" aria-hidden="true">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 12 20 22 4 22 4 12"></polyline>
                                    <rect x="2" y="7" width="20" height="5"></rect>
                                    <line x1="12" y1="22" x2="12" y2="7"></line>
                                    <path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"></path>
                                    <path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"></path>
                                </svg>
                            </div>
                            <div class="pw-gift__address-text">
                                <span class="pw-gift__address-label">Kirim kado</span>
                                <p class="pw-gift__address-val">{{ $gift['address'] }}</p>
                                <button type="button" class="pw-btn pw-gift__copy-addr" data-copy="{{ $gift['address'] }}">Salin Alamat</button>
                            </div>
                        </div>
                    @endif
                </div>
            </details>

            <p class="pw-gift__hint">Nomor rekening baru terlihat setelah dibuka.</p>
        @endif
    </section>

    <style>
        .pw-gift__reveal {
            margin-top: 1.4rem;
            text-align: center;
        }
        .pw-gift__toggle {
            width: 100%;
            max-width: 26rem;
            list-style: none;
            user-select: none;
            box-shadow: 0 4px 14px rgba(0, 0, 0, .08);
        }
        .pw-gift__toggle::-webkit-details-marker {
            display: none;
        }
        /* One button, two labels: the open/close text is chosen by the element's
           own state, so no script has to keep a caption in sync with it. */
        .pw-gift__reveal[open] .pw-gift__toggle-open,
        .pw-gift__reveal:not([open]) .pw-gift__toggle-close {
            display: none;
        }
        .pw-gift__body {
            margin-top: 1.1rem;
            text-align: initial;
            animation: pw-gift-open .32s cubic-bezier(0.16, 1, 0.3, 1) both;
        }
        .pw-gift__hint {
            max-width: 26rem;
            margin: .6rem auto 0;
            font-size: .68rem;
            letter-spacing: .04em;
            opacity: .6;
        }
        .pw-gift__reveal[open] + .pw-gift__hint {
            display: none;
        }
        @keyframes pw-gift-open {
            from { opacity: 0; transform: translateY(-.5rem); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (prefers-reduced-motion: reduce) {
            .pw-gift__body { animation: none; }
        }
        .pw-gift__list {
            display: grid;
            gap: 1.1rem;
            max-width: 26rem;
            margin: 0 auto;
            padding: 0;
            list-style: none;
        }
        /* Modern ATM Card Design */
        .pw-gift__card {
            position: relative;
            padding: 1.5rem 1.4rem 1.3rem;
            border: 1px solid var(--pw-line, rgba(200, 169, 106, .35));
            border-radius: var(--pw-radius, 14px);
            background: linear-gradient(135deg, rgba(255, 255, 255, .08) 0%, rgba(255, 255, 255, .03) 100%), var(--pw-surface, transparent);
            box-shadow: 0 10px 24px -8px rgba(0, 0, 0, .35);
            overflow: hidden;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
        .pw-gift__card::before {
            content: '';
            position: absolute;
            top: -40px;
            right: -40px;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: radial-gradient(circle, var(--pw-accent, rgba(200, 169, 106, .25)), transparent 70%);
            pointer-events: none;
        }
        .pw-gift__card-chip {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: .8rem;
        }
        .pw-gift__chip {
            display: block;
            width: 38px;
            height: 28px;
            border-radius: 5px;
            background:
                linear-gradient(135deg, rgba(255, 255, 255, .55), rgba(255, 255, 255, .12)),
                var(--pw-accent, #c8a96a);
            box-shadow: inset 0 0 0 1px rgba(0, 0, 0, .18);
            opacity: .8;
        }
        .pw-gift__chip::before {
            content: "";
            display: block;
            height: 100%;
            background:
                linear-gradient(90deg, transparent 46%, rgba(0, 0, 0, .22) 46% 54%, transparent 54%),
                linear-gradient(180deg, transparent 46%, rgba(0, 0, 0, .22) 46% 54%, transparent 54%);
            border-radius: inherit;
        }
        .pw-gift__contactless {
            opacity: .6;
        }
        .pw-gift__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .pw-gift__bank {
            font-size: .88rem;
            font-weight: 700;
            letter-spacing: .15em;
            text-transform: uppercase;
            color: var(--pw-accent, inherit);
        }
        .pw-gift__num {
            margin: .65rem 0 .95rem;
            font-family: var(--pw-numeric, 'Courier New', monospace);
            font-size: clamp(1.25rem, 5.5vw, 1.55rem);
            font-weight: 600;
            letter-spacing: .18em;
            font-variant-numeric: tabular-nums;
            overflow-wrap: anywhere;
            text-shadow: 0 1px 2px rgba(0, 0, 0, .3);
        }
        .pw-gift__footer {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            border-top: 1px dashed var(--pw-line, rgba(200, 169, 106, .2));
            padding-top: .75rem;
        }
        .pw-gift__holder {
            font-size: .82rem;
            letter-spacing: .06em;
            text-transform: uppercase;
            opacity: .85;
        }
        .pw-gift__copy {
            min-height: 34px;
            padding: .35rem .95rem;
            font-size: .68rem;
            letter-spacing: .08em;
            border-radius: 999px;
            background: var(--pw-accent, rgba(200, 169, 106, .2));
            color: var(--pw-accent-ink, #000);
            border-color: var(--pw-accent, rgba(200, 169, 106, .4));
        }
        .pw-gift__address-card {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            max-width: 26rem;
            margin: 1.15rem auto 0;
            padding: 1.15rem 1.25rem;
            border: 1px solid var(--pw-line, rgba(200, 169, 106, .3));
            border-radius: var(--pw-radius, 14px);
            background: var(--pw-surface, rgba(255, 255, 255, .04));
            box-shadow: 0 4px 16px rgba(0, 0, 0, .08);
        }
        .pw-gift__address-icon {
            flex-shrink: 0;
            color: var(--pw-accent, inherit);
            margin-top: .15rem;
        }
        .pw-gift__address-text {
            flex-grow: 1;
        }
        .pw-gift__address-label {
            display: block;
            font-size: .62rem;
            letter-spacing: .2em;
            text-transform: uppercase;
            color: var(--pw-accent, inherit);
            font-weight: 600;
        }
        .pw-gift__address-val {
            margin: .3rem 0 .7rem;
            font-size: .88rem;
            line-height: 1.55;
            opacity: .92;
        }
        .pw-gift__copy-addr {
            font-size: .65rem;
            padding: .3rem .8rem;
            min-height: 30px;
        }
    </style>
@endif
