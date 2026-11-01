{{--
    Akun dinonaktifkan oleh operator. Tujuannya menjelaskan, bukan menghukum:
    sertakan alasan yang sama dengan audit log dan tegaskan data tidak dihapus.
--}}
@extends('mail.layout')

@section('title', 'Akun dinonaktifkan sementara')
@section('preheader', 'Akses akun Anda dihentikan sementara. Data undangan tetap aman.')
@section('eyebrow', 'Akses dibatasi')
@section('heading', 'Akun Anda dinonaktifkan sementara')
@section('footer-note', 'Email ini dikirim otomatis karena status akun Anda berubah. Undangan, pesanan, dan data tamu tidak dihapus.')

@section('content')
    @include('mail.partials.paragraph', [
        'text' => 'Halo '.$name.', akses akun Anda ke '.config('app.name').' kami hentikan sementara. Undangan, pesanan, dan data tamu tetap tersimpan; tidak ada yang dihapus.',
    ])

    @include('mail.partials.details', ['rows' => [
        ['label' => 'Alasan dari operator', 'value' => $reason],
        ['label' => 'Waktu', 'value' => $when],
    ]])

    @include('mail.partials.button', ['label' => 'Tinjau keputusan ini', 'url' => $supportUrl])

    @include('mail.partials.paragraph', ['text' => 'Balas email ini atau hubungi kami di '.$phone.' bila Anda ingin meninjau keputusan ini atau menjelaskan keadaan yang sebenarnya.', 'muted' => true])
@endsection
