{{ config('app.name') }} — pesan baru masuk

{{ $name }} mengirim pesan melalui {{ $source }}.

@foreach ($rows as $row)
    {{ $row['label'] }}: {{ $row['value'] }}
@endforeach
@if ($enquiryMessage !== null)
Pesan:
"{{ $enquiryMessage }}"
@endif

Balas via email:
{{ $replyUrl }}
@if ($whatsappUrl !== null)

Balas via WhatsApp:
{{ $whatsappUrl }}
@endif

Masuk {{ $receivedAt }}. Balas dalam 1x24 jam agar calon pelanggan tidak
beralih ke studio lain.

@include('mail.partials.text-signature')
