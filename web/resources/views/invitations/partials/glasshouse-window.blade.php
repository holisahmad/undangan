@php
    $class = $class ?? 'window';
@endphp
<svg class="{{ $class }}" viewBox="0 0 120 150" fill="none" stroke="currentColor" stroke-width="1"
     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    {{-- A conservatory window: arch, mullions, and the horizon behind the glass. --}}
    <path d="M16 142V58a44 44 0 0 1 88 0v84z"/>
    <path d="M8 142h104"/>
    <path d="M16 58h88M60 14v128M16 94h88"/>
    <path d="M16 118h88" stroke-dasharray="3 7" opacity=".7"/>
    <path d="M36 142v-24h24v24M84 142v-18H70" opacity=".55"/>
</svg>
