{{ config('app.name') }} — akun dinonaktifkan sementara

Halo {{ $name }}, akses akun Anda ke {{ config('app.name') }} kami hentikan sementara.
Undangan, pesanan, dan data tamu tetap tersimpan; tidak ada yang dihapus.

Alasan dari operator : {{ $reason }}
Waktu                : {{ $when }}

Tinjau keputusan ini:
{{ $supportUrl }}

Balas email ini atau hubungi kami di {{ $phone }} bila Anda ingin meninjau
keputusan ini atau menjelaskan keadaan yang sebenarnya.

@include('mail.partials.text-signature')
