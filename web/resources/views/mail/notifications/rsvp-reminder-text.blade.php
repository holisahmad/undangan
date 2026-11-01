{{ config('app.name') }} — konfirmasi kehadiran

Halo {{ $guestName }},

Kami belum menerima konfirmasi kehadiran Anda untuk pernikahan {{ $couple }}.
Konfirmasi membantu kami menyiapkan kursi dan konsumsi yang tepat.

@foreach ($rows as $row)
    {{ $row['label'] }}: {{ $row['value'] }}
@endforeach

Konfirmasi kehadiran Anda:
{{ $url }}

Tautan ini khusus untuk Anda dan menggantikan tautan undangan sebelumnya,
jadi simpan email ini sampai hari acara.

@include('mail.partials.text-signature')
