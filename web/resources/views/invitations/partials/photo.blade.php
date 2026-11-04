{{--
    One photo frame for every renderer.

    `src` comes from App\Support\Invitations\InvitationMedia, so it is either one
    of the couple's published photos or the demo set on a template sample page.
    When there is no photo the same frame holds a monogram, which keeps the
    layout intact without inventing imagery.
--}}
@php
    $src = $src ?? null;
    $class = $class ?? 'hero-arch';
    $alt = $alt ?? 'Foto mempelai';
    $initial = $initial ?? '';
    $size = $size ?? ['width' => 480, 'height' => 600];
@endphp

@if ($src !== null)
    <div class="{{ $class }}">
        <img src="{{ $src }}" alt="{{ $alt }}" width="{{ $size['width'] }}" height="{{ $size['height'] }}" loading="lazy" decoding="async">
    </div>
@elseif ($initial !== '')
    <div class="{{ $class }} inv-monogram" role="img" aria-label="{{ $alt }}">
        <span>{{ $initial }}</span>
    </div>
@endif
