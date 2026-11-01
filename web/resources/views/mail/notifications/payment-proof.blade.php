{{--
    Notifikasi bukti transfer ke kotak masuk studio. Dana belum tercatat lunas
    di titik ini, jadi email menekankan bahwa keputusan verifikasi masih di
    tangan operator dan menyediakan tautan langsung ke panel pesanan.
--}}
@extends('mail.layout')

@section('title', 'Bukti transfer — '.$number)
@section('preheader', $senderName.' mengirim bukti transfer sebesar '.$amount.' untuk pesanan '.$number.'.')
@section('eyebrow', 'Bukti transfer masuk')
@section('heading', 'Bukti transfer dari '.$senderName)
@section('footer-note', 'Email ini dikirim ke kotak masuk studio setiap kali pelanggan mengirim bukti transfer. Pesanan baru aktif setelah dana diverifikasi di panel admin.')

@section('content')
    @include('mail.partials.details', ['rows' => $rows])

    @if ($proofNote !== null)
        @include('mail.partials.quote', ['text' => $proofNote])
    @endif

    @include('mail.partials.button', ['label' => 'Verifikasi di panel admin', 'url' => $adminUrl])

    @if ($replyUrl !== null)
        @include('mail.partials.button', ['label' => 'Hubungi pelanggan', 'url' => $replyUrl, 'variant' => 'secondary'])
    @endif

    @include('mail.partials.paragraph', ['text' => 'Masuk '.$receivedAt.'. Cocokkan nominal '.$amount.' dengan mutasi rekening sebelum menekan verifikasi; undangan pelanggan belum aktif sampai langkah itu selesai.', 'muted' => true])
@endsection
