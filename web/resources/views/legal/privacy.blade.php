@extends('layouts.marketing')

@section('title', 'Kebijakan privasi — '.config('app.name'))

@php
    $contact = config('site.contact');
    $updatedAt = '31 Oktober 2026';
@endphp

@section('meta')
    <meta name="description" content="Kebijakan privasi: data apa yang kami simpan, apa yang tampil publik, berapa lama data disimpan, dan cara meminta penghapusan.">
    <link rel="canonical" href="{{ route('legal.privacy') }}">
    <meta property="og:type" content="article">
    <meta property="og:title" content="Kebijakan privasi — {{ config('app.name') }}">
    <meta property="og:description" content="Data yang kami simpan, apa yang tampil publik, dan cara meminta penghapusan.">
    <meta property="og:url" content="{{ route('legal.privacy') }}">
@endsection

@section('content')
    <article class="mx-auto max-w-3xl px-4 py-14">
        <p class="text-xs uppercase tracking-[0.28em] text-rose-800">Legal</p>
        <h1 class="mt-3 font-serif text-3xl font-semibold">Kebijakan privasi</h1>
        <p class="mt-2 text-sm text-stone-500">Terakhir diperbarui {{ $updatedAt }}</p>

        <div class="mt-8 space-y-8 text-sm leading-relaxed text-stone-700">
            <section>
                <h2 class="font-serif text-xl font-semibold text-stone-900">Data yang kami simpan</h2>
                <ul class="mt-3 list-disc space-y-1 pl-5">
                    <li>Data akun: nama, alamat email, dan kata sandi yang disimpan sebagai hash.</li>
                    <li>Data undangan: nama mempelai, acara, cerita, dan isi yang Anda tulis sendiri.</li>
                    <li>Data tamu: nama, grup, ukuran rombongan, serta telepon/email bila Anda mengisinya.</li>
                    <li>Foto galeri yang Anda unggah dan metadata teknisnya (ukuran, tipe, checksum).</li>
                    <li>Catatan audit tindakan penting, misalnya publikasi, perubahan paket, dan RSVP.</li>
                    <li>Isian formulir hubungi kami: nama, email, WhatsApp, pesan, sumber kunjungan, dan layanan yang Anda tanyakan (misalnya rias pengantin) agar pertanyaan diteruskan ke pihak yang tepat. Alamat IP dan tipe peramban tidak disimpan.</li>
                </ul>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-stone-900">Apa yang tampil publik</h2>
                <ul class="mt-3 list-disc space-y-1 pl-5">
                    <li>Halaman undangan hanya tampil setelah Anda menerbitkannya, dan berhenti tampil saat masa aktif berakhir.</li>
                    <li>Nomor telepon dan email tamu tidak pernah ditampilkan pada halaman undangan.</li>
                    <li>Halaman publik tidak memuat daftar tamu; tautan pribadi tamu tidak dapat membuka daftar tamu.</li>
                    <li>Halaman undangan diberi <code class="rounded bg-stone-100 px-1">noindex</code>; ini bukan mekanisme autentikasi, jadi jangan bagikan tautan ke pihak yang tidak berhak.</li>
                    <li>Foto hanya dapat diunduh lewat tautan bertanda tangan yang berlaku singkat (15 menit).</li>
                </ul>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-stone-900">Penyimpanan dan retensi</h2>
                <ul class="mt-3 list-disc space-y-1 pl-5">
                    <li>Masa aktif undangan mengikuti paket yang dibeli, dihitung sejak publikasi.</li>
                    <li>Setelah masa aktif berakhir, halaman publik dinonaktifkan dan data tetap tersimpan agar dapat diaktifkan kembali.</li>
                    <li>Arsip ZIP foto berlaku 7 hari, setelah itu berkas arsip dihapus otomatis oleh proses terjadwal.</li>
                    <li>Foto berstatus karantina dibersihkan setelah 24 jam.</li>
                    <li>Salinan cadangan memiliki jadwal retensi sendiri dan dapat bertahan lebih lama daripada data aktif.</li>
                </ul>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-stone-900">Permintaan akses dan penghapusan</h2>
                <p class="mt-3">
                    Anda dapat meminta salinan, koreksi, ekspor, atau penghapusan data dengan mengirim email ke
                    <a class="font-medium underline" href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a>.
                    Kami memverifikasi identitas dan kewenangan pemohon sebelum memproses, lalu mencatat penyelesaian
                    permintaan tanpa menyimpan data sensitif berlebihan pada log audit.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-stone-900">Cookie dan sesi</h2>
                <p class="mt-3">
                    Situs memakai cookie sesi untuk menjaga status masuk dan token CSRF pada formulir. Tidak ada
                    cookie iklan atau pelacak pihak ketiga.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-stone-900">Perubahan kebijakan</h2>
                <p class="mt-3">
                    Perubahan material akan ditandai dengan tanggal pembaruan di halaman ini. Untuk pertanyaan lain,
                    gunakan kontak di bawah.
                </p>
            </section>

            <section class="rounded-lg border border-stone-200 bg-white p-5">
                <h2 class="font-serif text-lg font-semibold text-stone-900">Kontak dan penyelenggara</h2>
                <p class="mt-2 text-sm">
                    Permintaan salinan, koreksi, ekspor, atau penghapusan data pribadi dilayani melalui kontak
                    berikut. Sertakan alamat email akun Anda agar kami dapat mencocokkan permintaan dengan data
                    yang tersimpan.
                </p>
                @include('partials.business-contact')
            </section>
        </div>
    </article>
@endsection
