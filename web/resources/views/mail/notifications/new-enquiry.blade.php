{{--
    Notifikasi enquiry ke kotak masuk studio. Pesan tamu dikutip apa adanya dan
    dua tombol balas (email/WhatsApp) ada di atas lipatan supaya bisa dijawab
    langsung dari ponsel tanpa membuka admin console.
--}}
@extends('mail.layout')

@section('title', 'Enquiry baru — '.$name)
@section('preheader', $name.' mengirim pesan melalui halaman kontak.')
@section('eyebrow', 'Pesan baru masuk')
@section('heading', 'Enquiry dari '.$name)
@section('footer-note', 'Email ini dikirim ke kotak masuk studio setiap ada pesan baru dari halaman kontak. Balas langsung ke alamat pengirim.')

@section('content')
    @include('mail.partials.details', ['rows' => $rows])

    @if ($enquiryMessage !== null)
        @include('mail.partials.quote', ['text' => $enquiryMessage])
    @endif

    @include('mail.partials.button', ['label' => 'Balas via email', 'url' => $replyUrl])

    @if ($whatsappUrl !== null)
        @include('mail.partials.button', ['label' => 'Balas via WhatsApp', 'url' => $whatsappUrl, 'variant' => 'secondary'])
    @endif

    @include('mail.partials.paragraph', ['text' => 'Masuk '.$receivedAt.' melalui '.$source.'. Balas dalam 1x24 jam agar calon pelanggan tidak beralih ke studio lain.', 'muted' => true])
@endsection
