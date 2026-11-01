@php
    $feed = $wishes ?? null;
@endphp

@if ($feed !== null && ($feed['total'] ?? 0) > 0)
    <section class="pw pw-wishes" id="ucapan" aria-label="Ucapan dan doa untuk mempelai">
        <p class="pw-eyebrow">Ucapan &amp; Doa</p>
        <h2 class="pw-title">Doa untuk Mempelai</h2>
        <p class="pw-lede">{{ $feed['total'] }} ucapan dari tamu yang telah mengisi konfirmasi kehadiran.</p>

        <ul class="pw-wishes__list">
            @foreach ($feed['items'] as $wish)
                <li class="pw-wish">
                    <span class="pw-wish__avatar" aria-hidden="true">{{ $wish['initial'] }}</span>
                    <div class="pw-wish__body">
                        <p class="pw-wish__name">{{ $wish['name'] }}</p>
                        <p class="pw-wish__text">{{ $wish['message'] }}</p>
                        @if ($wish['at'] !== '')
                            <p class="pw-wish__at">{{ $wish['at'] }}</p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>

        @if (($feed['pages'] ?? 1) > 1)
            <nav class="pw-pager" aria-label="Halaman ucapan">
                @for ($page = 1; $page <= $feed['pages']; $page++)
                    <a href="{{ request()->fullUrlWithQuery(['ucapan' => $page]).'#ucapan' }}"
                       @class(['pw-pager__link', 'is-current' => $page === $feed['page']])
                       @if ($page === $feed['page']) aria-current="page" @endif>{{ $page }}</a>
                @endfor
            </nav>
        @endif

        <p class="pw-wishes__note">Ucapan tampil karena tamu bersedia membagikannya saat mengisi RSVP.</p>
    </section>

    <style>
        .pw-wishes__list {
            display: grid;
            gap: .7rem;
            max-width: 30rem;
            margin: 1.4rem auto 0;
            padding: 0;
            list-style: none;
        }
        .pw-wish {
            display: flex;
            gap: .8rem;
            padding: .95rem 1.05rem;
            border: 1px solid var(--pw-line, rgba(0, 0, 0, .14));
            border-radius: var(--pw-radius, 12px);
            background: var(--pw-surface, transparent);
        }
        .pw-wish__avatar {
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 1px solid var(--pw-line, rgba(0, 0, 0, .2));
            font-size: .78rem;
            letter-spacing: .04em;
        }
        .pw-wish__body { min-width: 0; }
        .pw-wish__name {
            margin: 0;
            font-size: .82rem;
            font-weight: 600;
            letter-spacing: .04em;
        }
        .pw-wish__text {
            margin: .3rem 0 0;
            font-size: .9rem;
            line-height: 1.62;
            overflow-wrap: anywhere;
        }
        .pw-wish__at {
            margin: .45rem 0 0;
            font-size: .64rem;
            letter-spacing: .12em;
            text-transform: uppercase;
            opacity: .6;
        }
        .pw-pager {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: .4rem;
            margin: 1.15rem 0 0;
        }
        .pw-pager__link {
            display: grid;
            place-items: center;
            min-width: 36px;
            height: 36px;
            padding: 0 .5rem;
            border: 1px solid var(--pw-line, rgba(0, 0, 0, .18));
            border-radius: 999px;
            font-size: .78rem;
            text-decoration: none;
            color: inherit;
        }
        .pw-pager__link.is-current {
            border-color: var(--pw-accent, currentColor);
            background: var(--pw-accent, currentColor);
            color: var(--pw-accent-ink, #fff);
        }
        .pw-wishes__note {
            margin: 1rem 0 0;
            font-size: .66rem;
            letter-spacing: .04em;
            text-align: center;
            opacity: .55;
        }
    </style>
@endif
