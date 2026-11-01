{{--
    The studio a customer would call or visit: name, email, phone, address, map.
    Rendered on the legal pages and anywhere a full answer to "how do I reach
    you?" belongs. Values come from config/site.php and are never retyped here.
--}}
@php
    $contact = config('site.contact');
    $addressLines = App\Support\Contact\BusinessAddress::lines((array) config('site.address', []));
    $phone = App\Support\Contact\PhoneNumber::display((string) ($contact['phone'] ?? ''));
    $phoneHref = App\Support\Contact\PhoneNumber::e164((string) ($contact['phone'] ?? ''));
    $whatsappHref = App\Support\Contact\PhoneNumber::whatsappLink((string) ($contact['whatsapp'] ?? ''));
    $mapsUrl = (string) ($contact['maps_url'] ?? '');
@endphp

<div class="mt-3 space-y-1 text-sm leading-relaxed text-stone-700">
    <p class="font-medium text-stone-900">{{ config('site.operator') }}</p>
    <p>
        <a class="underline" href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a>
    </p>
    @if ($phoneHref !== null)
        <p>
            <a class="underline" href="tel:{{ $phoneHref }}">{{ $phone }}</a>
            @if ($whatsappHref !== null)
                <span class="text-stone-400" aria-hidden="true">&middot;</span>
                <a class="underline" href="{{ $whatsappHref }}" target="_blank" rel="noopener noreferrer">WhatsApp</a>
            @endif
        </p>
    @endif
    @if ($addressLines !== [])
        <address class="not-italic text-stone-600">
            @foreach ($addressLines as $line)
                {{ $line }}@if (! $loop->last)<br>@endif
            @endforeach
            @if ($mapsUrl !== '')
                <br>
                <a class="underline" href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer">Buka di Google Maps</a>
            @endif
        </address>
    @endif
    <p class="pt-1 text-xs text-stone-500">{{ $contact['hours'] }} &middot; {{ $contact['response'] }}</p>
</div>
