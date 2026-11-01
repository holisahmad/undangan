{{--
    Pengingat RSVP untuk tamu. Tautan pribadi di dalamnya baru diterbitkan
    ulang, jadi tautan lama tidak lagi berlaku (lihat SendRsvpReminders).
--}}
@extends('mail.layout')

@section('title', 'Konfirmasi kehadiran — '.$couple)
@section('preheader', 'Kami belum menerima konfirmasi kehadiran Anda untuk pernikahan '.$couple.'.')
@section('eyebrow', 'Konfirmasi kehadiran')
@section('heading', 'Halo '.$guestName.', satu langkah lagi')
@section('footer-note', 'Email ini dikirim karena Anda tercatat sebagai tamu undangan. Bila sudah mengonfirmasi, abaikan saja email ini.')

@section('content')
    @include('mail.partials.paragraph', [
        'text' => 'Kami belum menerima konfirmasi kehadiran Anda untuk pernikahan '.$couple.'. Konfirmasi membantu kami menyiapkan kursi dan konsumsi yang tepat.',
    ])

    @include('mail.partials.details', ['rows' => $rows])

    @include('mail.partials.button', ['label' => 'Konfirmasi kehadiran', 'url' => $url])

    @include('mail.partials.paragraph', ['text' => 'Tautan ini khusus untuk Anda dan menggantikan tautan undangan sebelumnya, jadi simpan email ini sampai hari acara.', 'muted' => true])
@endsection
