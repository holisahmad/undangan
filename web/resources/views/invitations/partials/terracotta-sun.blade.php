@php
    $class = $class ?? 'sun';
@endphp
<svg class="{{ $class }}" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-width="1.1"
     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    {{-- Sun setting behind a low horizon: the hour the akad begins. --}}
    <circle cx="60" cy="58" r="21"/>
    <path d="M60 22v-8M60 102v-8M24 58H16M104 58h-8M34 32l-5.6-5.6M91.6 89.6 86 84M86 32l5.6-5.6M28.4 89.6 34 84"/>
    <path d="M18 84h84M30 96h60" stroke-dasharray="4 6"/>
</svg>
