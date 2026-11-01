{{--
    Pembayaran terverifikasi. Dikirim ke pelanggan setelah webhook/konfirmasi
    membayar pesanan, jadi nadanya mengonfirmasi, bukan menagih.
--}}
@extends('mail.layout')

@section('title', 'Pembayaran diterima — '.$number)
@section('preheader', 'Pesanan '.$number.' sudah lunas dan undangan '.$couple.' aktif.')
@section('eyebrow', 'Pembayaran terverifikasi')
@section('heading', 'Pesanan '.$number.' sudah lunas')

@section('content')
    @include('mail.partials.paragraph', [
        'text' => 'Halo '.$name.', pembayaran Anda sudah kami terima dan verifikasi. Undangan '.$couple.' sekarang aktif dan siap dibagikan ke tamu.',
    ])

    @include('mail.partials.details', ['rows' => [
        ['label' => 'Nomor pesanan', 'value' => $number],
        ['label' => 'Undangan', 'value' => $couple],
        ['label' => 'Total dibayar', 'value' => $total],
        ['label' => 'Diverifikasi', 'value' => $paidAt],
    ]])

    @include('mail.partials.button', ['label' => 'Buka pesanan saya', 'url' => $url])

    @include('mail.partials.paragraph', ['text' => 'Simpan email ini sebagai bukti pembayaran. Bila ada yang tidak sesuai, cukup balas email ini dan sebutkan nomor pesanan di atas.', 'muted' => true])
@endsection
