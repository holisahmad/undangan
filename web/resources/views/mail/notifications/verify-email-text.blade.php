{{ config('app.name') }} — konfirmasi email Anda

Halo {{ $name }},

Konfirmasi alamat email Anda supaya undangan, pesanan, dan data tamu bisa
diakses. Tautan ini hanya berlaku sekali dan kedaluwarsa dalam
{{ $expiresIn }} menit.

Akun          : {{ $name }}
Alamat email  : {{ $email }}
Berlaku sampai: {{ $expiresAt }}

Verifikasi email:
{{ $url }}

Tombol tidak berfungsi? Salin tautan di atas ke peramban.

Bila Anda tidak merasa mendaftar di {{ config('app.name') }}, abaikan email ini. Alamat Anda
tidak akan dipakai tanpa konfirmasi.

@include('mail.partials.text-signature')
