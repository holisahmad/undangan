{{--
    Verifikasi alamat email. Menggantikan template bawaan Laravel supaya akun
    baru menerima email yang sama rapi dan jelasnya dengan email transaksi lain.
--}}
@extends('mail.layout')

@section('title', 'Konfirmasi email Anda — '.config('app.name'))
@section('preheader', 'Satu klik untuk mengaktifkan akun '.config('app.name').' Anda.')
@section('eyebrow', 'Konfirmasi email')
@section('heading', 'Satu langkah lagi, '.$name)
@section('footer-note', 'Email ini dikirim otomatis karena ada pendaftaran atau permintaan kirim ulang verifikasi. Bila bukan Anda, abaikan saja.')

@section('content')
    @include('mail.partials.paragraph', [
        'text' => 'Konfirmasi alamat email Anda supaya undangan, pesanan, dan data tamu bisa diakses. Tautan ini hanya berlaku sekali dan kedaluwarsa dalam '.$expiresIn.' menit.',
    ])

    @include('mail.partials.details', ['rows' => [
        ['label' => 'Akun', 'value' => $name],
        ['label' => 'Alamat email', 'value' => $email],
        ['label' => 'Berlaku sampai', 'value' => $expiresAt],
    ]])

    @include('mail.partials.button', ['label' => 'Verifikasi email', 'url' => $url])

    @include('mail.partials.paragraph', ['text' => 'Tombol tidak berfungsi? Salin tautan berikut ke peramban.', 'muted' => true])

    @include('mail.partials.url', ['url' => $url])

    @include('mail.partials.paragraph', ['text' => 'Bila Anda tidak merasa mendaftar di '.config('app.name').', abaikan email ini. Alamat Anda tidak akan dipakai tanpa konfirmasi.', 'muted' => true])
@endsection
