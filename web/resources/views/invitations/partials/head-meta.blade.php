@php
    $headline = $invitation->coupleHeadline() ?: 'Undangan Pernikahan';
    $firstEvent = $invitation->events[0] ?? [];
    $when = $firstEvent['starts_label'] ?? null;

    if ($when === null && ! empty($firstEvent['starts_at'])) {
        try {
            $when = \Illuminate\Support\Carbon::parse((string) $firstEvent['starts_at'])
                ->locale('id')
                ->translatedFormat('d F Y');
        } catch (\Throwable) {
            $when = null;
        }
    }

    $place = $firstEvent['venue_name'] ?? null;
    $summary = trim(implode(' · ', array_filter([$headline, $when, $place])));
    $description = 'Undangan pernikahan '.$summary.'.';
@endphp
<meta name="description" content="{{ $description }}">
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ config('app.name') }}">
<meta property="og:title" content="{{ $headline }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:locale" content="id_ID">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="{{ $headline }}">
<meta name="twitter:description" content="{{ $description }}">

{{-- Elegant Typography for Wedding Invitations --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Cinzel:wght@400..700&family=Cormorant+Garamond:ital,wght@0,300..700;1,300..700&family=Great+Vibes&family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Plus+Jakarta+Sans:wght@300..700&display=swap" rel="stylesheet">

{{-- Shared token-driven foundation: photo frames, gallery, reveal and the
     themed RSVP form. Renderers no longer carry their own copy of these rules. --}}
@include('invitations.partials.theme-base')

{{-- A guest who saves the invitation to the home screen keeps our icon. --}}
@include('partials.brand-head')

{{-- Only the fictional template demos and the owner-approved portfolio are
     meant to be indexed; a real invitation carries a couple's names, venue and
     address. --}}
@if ($showcase ?? false)
    <link rel="canonical" href="{{ $showcaseUrl }}">
    <meta name="robots" content="index,follow">
@elseif ($demo ?? false)
    <link rel="canonical" href="{{ route('templates.demo', ['template' => $invitation->templateSlug]) }}">
    <meta name="robots" content="index,follow">
@else
    <meta name="robots" content="noindex,nofollow">
@endif
