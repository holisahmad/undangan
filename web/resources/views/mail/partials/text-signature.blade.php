{{-- Tanda tangan teks polos untuk versi text/*.blade.php. --}}
@php
    $operatorName = (string) config('site.operator');
    $operatorAddress = implode(', ', App\Support\Contact\BusinessAddress::lines((array) config('site.address', [])));
    $operatorEmail = (string) config('site.contact.email');
    $operatorPhone = App\Support\Contact\PhoneNumber::display((string) config('site.contact.phone'));
    $operatorHome = route('home');
@endphp
--
{{ $operatorName }}
{{ $operatorAddress }}
{{ $operatorEmail }} · {{ $operatorPhone }}
{{ $operatorHome }}
