{{ config('app.name') }} — pembayaran diterima

Halo {{ $name }}, pembayaran Anda sudah kami terima dan verifikasi.
Undangan {{ $couple }} sekarang aktif dan siap dibagikan ke tamu.

Nomor pesanan : {{ $number }}
Undangan      : {{ $couple }}
Total dibayar : {{ $total }}
Diverifikasi  : {{ $paidAt }}

Buka pesanan Anda:
{{ $url }}

Simpan email ini sebagai bukti pembayaran. Bila ada yang tidak sesuai, cukup
balas email ini dan sebutkan nomor pesanan di atas.

@include('mail.partials.text-signature')
