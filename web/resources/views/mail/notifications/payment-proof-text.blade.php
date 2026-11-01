{{ config('app.name') }} — bukti transfer masuk

{{ $senderName }} mengirim bukti transfer untuk pesanan {{ $number }}.

@foreach ($rows as $row)
    {{ $row['label'] }}: {{ $row['value'] }}
@endforeach
@if ($proofNote !== null)
Catatan pelanggan:
"{{ $proofNote }}"
@endif

Verifikasi di panel admin:
{{ $adminUrl }}
@if ($replyUrl !== null)

Hubungi pelanggan:
{{ $replyUrl }}
@endif

Masuk {{ $receivedAt }}. Cocokkan nominal {{ $amount }} dengan mutasi
rekening sebelum menekan verifikasi; undangan pelanggan belum aktif sampai
langkah itu selesai.

@include('mail.partials.text-signature')
