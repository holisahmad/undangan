@php
    $version = $version ?? $template->publishedVersion();
    $demoUrl = $version === null ? null : route('templates.demo', ['template' => $template->slug]);
    $useUrl = route('weddings.create', ['template' => $template->slug]);

    // Colour, art direction, category wording and the package note are catalogue
    // data that lives with the renderer, so a card never invents its own
    // vocabulary and never shows a design it is not.
    $theme = App\Support\Templates\TemplatePresentation::card($template);
    $badge = App\Support\Templates\TemplatePresentation::badgeLabel($template->badge);
    $category = App\Support\Templates\TemplatePresentation::categoryLabel($template->category);
    $tier = App\Support\Templates\TemplatePresentation::tierLabel($template->tier);
    $note = $unlockNotes[$template->slug] ?? null;
@endphp

<article class="tpl-card group"
         style="--paper: {{ $theme['paper'] }}; --ink: {{ $theme['ink'] }}; --accent: {{ $theme['accent'] }}; --line: {{ $theme['line'] }}; --display: {{ $theme['display'] }}; --script: {{ $theme['script'] !== '' ? $theme['script'] : $theme['display'] }};">
    <div class="tpl-stage">
        @if ($badge !== null)
            <span class="tpl-badge">{{ $badge }}</span>
        @endif

        @include('templates.partials.mini-cover', ['theme' => $theme])

        @if ($demoUrl !== null)
            <a class="tpl-stage__open" href="{{ $demoUrl }}"
               aria-label="Buka demo tema {{ $template->name }}">
                Buka demo
                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>
                </svg>
            </a>
        @endif
    </div>

    <div class="tpl-body">
        <p class="tpl-meta">{{ $category }} <span aria-hidden="true">·</span> {{ $tier }}</p>
        <h3 class="tpl-name">{{ $template->name }}</h3>
        <p class="tpl-desc">{{ $template->description ?? 'Undangan pernikahan dengan tata letak yang tenang dan terbaca.' }}</p>

        @if ($note !== null)
            <p class="tpl-lock">{{ $note }}</p>
        @endif

        <div class="tpl-actions">
            @if ($demoUrl !== null)
                <a href="{{ $demoUrl }}" class="tpl-link">Lihat contoh</a>
            @else
                <span class="tpl-link">Segera hadir</span>
            @endif
            <a href="{{ $useUrl }}" class="tpl-link tpl-link--strong">
                Gunakan<span aria-hidden="true"> →</span>
            </a>
        </div>
    </div>
</article>
