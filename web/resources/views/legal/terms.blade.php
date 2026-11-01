@extends('layouts.marketing')

@section('title', 'Syarat & ketentuan — '.config('app.name'))

@php
    $contact = config('site.contact');
    $updatedAt = '31 Oktober 2026';
@endphp

@section('meta')
    <meta name="description" content="Syarat dan ketentuan penggunaan layanan undangan pernikahan digital: akun, konten, pembayaran, masa aktif, dan batas tanggung jawab.">
    <link rel="canonical" href="{{ route('legal.terms') }}">
    <meta property="og:type" content="article">
    <meta property="og:title" content="Syarat & ketentuan — {{ config('app.name') }}">
    <meta property="og:description" content="Ketentuan akun, konten, pembayaran, masa aktif, dan batas tanggung jawab.">
    <meta property="og:url" content="{{ route('legal.terms') }}">
@endsection

@section('content')
    <article class="mx-auto max-w-3xl px-4 py-14">
        <p class="text-xs uppercase tracking-[0.28em] text-rose-800">Legal</p>
        <h1 class="mt-3 font-serif text-3xl font-semibold">Syarat &amp; ketentuan</h1>
        <p class="mt-2 text-sm text-stone-500">Terakhir diperbarui {{ $updatedAt }}</p>

        <div class="mt-8 space-y-8 text-sm leading-relaxed text-stone-700">
            <section>
                <h2 class="font-serif text-xl font-semibold text-stone-900">1. Layanan</h2>
                <p class="mt-3">
                    Kami menyediakan alat untuk menyusun undangan pernikahan digital, menerbitkannya sebagai tautan,
                    mengelola daftar tamu, menerima RSVP, dan menyimpan galeri foto. Layanan disediakan sebagaimana
                    adanya; fitur dapat berubah selama masa pengembangan dengan pemberitahuan pada halaman ini.
                </p>
                <ul class="mt-3 list-disc space-y-1 pl-5">
                    <li>Layanan pelaminan yang disebut di halaman depan (rias pengantin, dekorasi, dokumentasi, wedding organizer dan MC, cetak, hiburan) dikoordinasikan dengan vendor mitra. Kami menghubungkan Anda dan mencatat pertanyaannya; harga, jadwal, pelaksanaan, dan tanggung jawab atas layanan itu berada pada mitranya dan tidak termasuk paket undangan yang Anda beli.</li>
                </ul>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-stone-900">2. Akun dan keamanan</h2>
                <ul class="mt-3 list-disc space-y-1 pl-5">
                    <li>Anda bertanggung jawab menjaga kerahasiaan kata sandi dan tautan pribadi tamu.</li>
                    <li>Satu akun digunakan untuk satu penyelenggara; jangan membagikan kredensial.</li>
                    <li>Laporkan akses tidak sah ke {{ $contact['email'] }} agar tautan dapat dicabut.</li>
                </ul>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-stone-900">3. Konten yang Anda unggah</h2>
                <ul class="mt-3 list-disc space-y-1 pl-5">
                    <li>Anda menyatakan berhak atas teks dan foto yang diunggah, atau memiliki izin dari pemegang haknya.</li>
                    <li>Anda bertanggung jawab memastikan data tamu (nama, telepon, email) diperoleh dan digunakan dengan dasar yang sah.</li>
                    <li>Kami hanya memakai konten tersebut untuk menjalankan layanan dan tidak menjualnya.</li>
                    <li>Konten yang melanggar hukum, menyesatkan, atau melanggar hak pihak lain dapat dihapus.</li>
                </ul>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-stone-900">4. Pembayaran dan masa aktif</h2>
                <ul class="mt-3 list-disc space-y-1 pl-5">
                    <li>Harga dan kuota diambil dari katalog paket pada saat pesanan dibuat; harga tidak berasal dari browser.</li>
                    <li>Masa aktif dihitung sejak publikasi pertama dan berlaku selama durasi paket.</li>
                    <li>Masa aktif aktif otomatis setelah pembayaran terverifikasi. Tidak ada klaim manual.</li>
                    <li>Setelah masa aktif berakhir, halaman publik dinonaktifkan. Data tetap tersimpan dan dapat diaktifkan kembali dengan paket baru.</li>
                </ul>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-stone-900">5. Publikasi dan penghentian</h2>
                <ul class="mt-3 list-disc space-y-1 pl-5">
                    <li>Anda dapat menarik undangan kapan saja; tautan yang beredar akan menampilkan halaman tidak tersedia.</li>
                    <li>Kami dapat menonaktifkan undangan yang melanggar ketentuan ini atau hukum yang berlaku, dan mencatat tindakan tersebut pada log audit.</li>
                    <li>Tautan tamu bersifat rahasia dan tidak boleh dipublikasikan terbuka.</li>
                </ul>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-stone-900">6. Batas tanggung jawab</h2>
                <p class="mt-3">
                    Kami berupaya menjaga ketersediaan layanan, tetapi tidak menjamin layanan bebas gangguan. Sejauh
                    diizinkan hukum, tanggung jawab kami atas kerugian tidak langsung dibatasi pada nilai paket yang
                    dibeli. Cadangkan sendiri konten penting: foto dapat diunduh lewat arsip, dan data tamu dapat
                    diekspor ke CSV.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-stone-900">7. Perubahan ketentuan</h2>
                <p class="mt-3">
                    Perubahan material akan ditandai dengan tanggal pembaruan di halaman ini. Dengan terus memakai
                    layanan setelah perubahan berlaku, Anda menyetujui ketentuan yang diperbarui.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-stone-900">8. Hukum yang berlaku</h2>
                <p class="mt-3">Ketentuan ini tunduk pada hukum Republik Indonesia.</p>
            </section>

            <section class="rounded-lg border border-stone-200 bg-white p-5">
                <h2 class="font-serif text-lg font-semibold text-stone-900">9. Kontak</h2>
                <p class="mt-2 text-sm">
                    Pertanyaan tentang ketentuan ini, pembayaran, atau masa aktif undangan dilayani melalui
                    kontak berikut.
                </p>
                @include('partials.business-contact')
            </section>
        </div>
    </article>
@endsection
