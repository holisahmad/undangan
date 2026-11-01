@php
    $class = $class ?? 'moon';
@endphp
<svg class="{{ $class }}" viewBox="0 0 96 96" fill="none" stroke="currentColor" stroke-width="1.1"
     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    {{-- Crescent moon with a four-point star: the night the two of them chose. --}}
    <path d="M58 12a36 36 0 1 0 0 72 30 30 0 0 1 0-72z"/>
    <path d="M66 30l2.6 5.4L74 38l-5.4 2.6L66 46l-2.6-5.4L58 38l5.4-2.6z"/>
    <path d="M74 54l1.6 3.4L79 59l-3.4 1.6L74 64l-1.6-3.4L69 59l3.4-1.6z"/>
    <circle cx="30" cy="26" r="1.4" fill="currentColor" stroke="none"/>
    <circle cx="22" cy="66" r="1.2" fill="currentColor" stroke="none"/>
    <circle cx="40" cy="80" r="1" fill="currentColor" stroke="none"/>
</svg>
