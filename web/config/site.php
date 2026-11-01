<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Public marketing content
|--------------------------------------------------------------------------
|
| Copy shown on the landing and legal pages. Contact details come from the
| environment so a deployment never advertises a placeholder address it does
| not monitor (docs/PRIVACY_AND_RETENTION.md, section 7). The postal address is
| stored here as parts because the same values feed the page and schema.org.
| Keep them in sync with the Google Maps listing.
|
*/

return [
    'tagline' => 'Invitation · Celebration · Forever',

    // Shown in the footer of every public page. The builder is credited here
    // and nowhere else, so a rebrand touches a single string.
    'credit' => 'Dibangun oleh Numos Studio',

    // The business behind the product. "Wedly" is the product name (app.name),
    // this is the studio a customer is actually paying and visiting.
    'operator' => 'Wedly Studio',

    'address' => [
        'street' => 'Perumahan Jatimas Permai, Jl. Melati No. 17 Blok E',
        'locality' => 'Perengdawe, Balecatur, Kec. Gamping',
        'region' => 'Kabupaten Sleman, Daerah Istimewa Yogyakarta',
        'postal_code' => '55295',
        'country' => 'ID',
    ],

    'description' => 'Undangan pernikahan digital dengan tautan pribadi per tamu, RSVP otomatis, galeri foto, dan masa aktif yang jelas. Susun, terbitkan, dan pantau dari satu dasbor.',

    /*
    |--------------------------------------------------------------------------
    | Landing capability grid
    |--------------------------------------------------------------------------
    |
    | Every entry names something the product already does, because a landing
    | page is a promise a customer will hold us to. Each line is covered by a
    | feature test; remove the capability and the entry has to go with it.
    |
    */
    'features' => [
        ['title' => 'Tautan pribadi per tamu', 'text' => 'Setiap tamu membuka undangan lewat URL uniknya sendiri, dengan namanya di halaman. Daftar tamu tidak dapat dibuka dari tautan itu.'],
        ['title' => 'RSVP dan jumlah rombongan', 'text' => 'Hadir, masih ragu, atau tidak hadir, beserta jumlah orang sesuai kuota. Respons terbaru menggantikan yang lama tanpa perlu formulir lain.'],
        ['title' => 'Sampul undangan tanpa skrip', 'text' => 'Sampul “Buka Undangan” bekerja dengan HTML murni. Tamu dengan JavaScript mati tetap bisa membaca seluruh isi.'],
        ['title' => 'Hitung mundur & tombol kalender', 'text' => 'Menuju hari-H dihitung di server, dan berkas kalender .ics dibuat dari acara yang sama sehingga tanggal tersimpan rapi di ponsel tamu.'],
        ['title' => 'Galeri foto & album', 'text' => 'Unggah pre-wedding atau dokumentasi, atur per album, tentukan mana yang publik. Foto disajikan lewat tautan bertanda tangan berumur pendek.'],
        ['title' => 'Unduh semua foto sekali klik', 'text' => 'Satu arsip ZIP berisi seluruh foto siap tayang, dibuat atas permintaan dan tersedia selama 7 hari.'],
        ['title' => 'Ucapan & doa dari tamu', 'text' => 'Pesan tampil di dinding ucapan hanya jika tamu mengizinkan, dan dapat Anda sembunyikan sebelum terlihat orang lain.'],
        ['title' => 'Amplop digital', 'text' => 'Nomor rekening dengan tombol salin dan nama pemiliknya, plus alamat kirim kado bila Anda menerimanya fisik.'],
        ['title' => 'Lokasi dan jadwal yang jelas', 'text' => 'Akad dan resepsi ditulis terpisah lengkap dengan tautan peta, sehingga tamu tidak perlu menebak waktunya.'],
        ['title' => 'Draf tersimpan otomatis', 'text' => 'Suntingan disimpan saat Anda berhenti mengetik. Terbitkan ulang hanya ketika sudah siap; tautan lama tetap memakai versi terbit.'],
        ['title' => 'Kuota dan masa aktif tertulis', 'text' => 'Jumlah tamu, jumlah foto, dan lama aktif tercantum per paket. Setelah berakhir, halaman dapat diaktifkan lagi tanpa kehilangan data.'],
        ['title' => 'Data tamu tidak dijual ke siapa pun', 'text' => 'Nomor telepon dan email tamu tidak pernah tampil di halaman undangan, dan tidak ikut ditampilkan di portofolio.'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Wedding services
    |--------------------------------------------------------------------------
    |
    | The platform delivers the digital invitation only. Everything else is
    | offered as a coordination service with partners: the card says so, and an
    | enquiry arrives with the interest already attached (leads.service_interest).
    | Set services_phone to the number that actually handles these enquiries.
    |
    */
    'services' => [
        ['slug' => 'rias-pengantin', 'name' => 'Rias Pengantin', 'text' => 'Palet makeup dan uji coba yang cocok dengan tema dan warna kebaya Anda.', 'provider' => 'MUA mitra'],
        ['slug' => 'dekorasi-pelaminan', 'name' => 'Dekorasi Pelaminan', 'text' => 'Riasan panggung, bunga, dan lighting selaras dengan tema undangan digital.', 'provider' => 'Dekorator mitra'],
        ['slug' => 'dokumentasi', 'name' => 'Dokumentasi Foto & Video', 'text' => 'Foto akad hingga resepsi, lalu hasilnya masuk ke galeri undangan Anda.', 'provider' => 'Fotografer mitra'],
        ['slug' => 'cetak-undangan', 'name' => 'Cetak Undangan', 'text' => 'Buku tamu, sertifikat, dan kartu undangan fisik dengan desain yang sama.', 'provider' => 'Percetakan mitra'],
        ['slug' => 'wedding-organizer', 'name' => 'Wedding Organizer & MC', 'text' => 'Susunan acara, pimpinan acara, dan koordinasi harian di lokasi.', 'provider' => 'WO mitra'],
        ['slug' => 'hiburan', 'name' => 'Hiburan Acara', 'text' => 'Organ tunggal, string section, atau sound system untuk resepsi Anda.', 'provider' => 'Vendor mitra'],
    ],

    // The number a service enquiry should be sent to. Empty keeps the lead form
    // as the only route so a deployment never advertises an unmonitored line.
    'services_phone' => env('SERVICES_PHONE', ''),

    /*
    |--------------------------------------------------------------------------
    | Seasonal promotion
    |--------------------------------------------------------------------------
    |
    | The countdown is only rendered when a real deadline is configured; a
    | marketing clock that never expires is worse than no clock at all.
    |
    */
    'promo' => [
        'label' => env('PROMO_LABEL', ''),
        'note' => env('PROMO_NOTE', ''),
        'ends_at' => env('PROMO_ENDS_AT', ''),
    ],

    'contact' => [
        'email' => env('CONTACT_EMAIL', 'lencakstudio@gmail.com'),
        'phone' => env('CONTACT_PHONE', '081391515363'),
        'whatsapp' => env('CONTACT_WHATSAPP', '081391515363'),
        'maps_url' => env('CONTACT_MAPS_URL', 'https://maps.app.goo.gl/Ejsyx1UxHtUw2LZT6'),
        'hours' => 'Senin–Sabtu, 09.00–18.00 WIB',
        'response' => 'Kami membalas pada hari kerja, maksimal 1x24 jam.',
    ],

    'faq' => [
        [
            'question' => 'Apakah undangan nyaman dibuka di ponsel?',
            'answer' => 'Ya. Halaman undangan dirancang lebih dulu untuk layar ponsel, lalu tetap rapi di tablet dan komputer. Tidak ada aplikasi yang perlu dipasang tamu.',
        ],
        [
            'question' => 'Apakah tamu perlu memasang aplikasi?',
            'answer' => 'Tidak. Undangan dibuka langsung di browser melalui tautan yang Anda bagikan, baik di ponsel maupun komputer.',
        ],
        [
            'question' => 'Bagaimana tamu mengisi RSVP?',
            'answer' => 'Setiap tamu menerima tautan pribadi. Di halaman itu tamu memilih hadir, masih ragu, atau tidak hadir, menentukan jumlah rombongan sesuai kuota, dan menulis pesan. Respons terbaru menggantikan respons sebelumnya.',
        ],
        [
            'question' => 'Apakah data tamu bisa dilihat publik?',
            'answer' => 'Tidak. Nomor telepon dan email tamu tidak pernah tampil di halaman undangan, daftar tamu tidak dapat dibuka dari tautan tamu, dan halaman publik tidak memuat nama tamu.',
        ],
        [
            'question' => 'Apakah perubahan draf langsung mengubah undangan yang sudah dibagikan?',
            'answer' => 'Tidak. Tautan yang sudah dibagikan memakai versi terbit. Perubahan draf baru berlaku setelah Anda menerbitkan ulang, sehingga tamu tidak melihat halaman setengah jadi.',
        ],
        [
            'question' => 'Berapa lama undangan aktif?',
            'answer' => 'Masa aktif mengikuti paket yang dipilih (12, 18, atau 24 bulan sejak publikasi). Setelah berakhir, halaman publik dinonaktifkan dan dapat diaktifkan kembali dengan paket baru.',
        ],
        [
            'question' => 'Bisakah semua foto diunduh sekaligus?',
            'answer' => 'Bisa. Setiap undangan dapat membuat arsip ZIP dari seluruh foto siap tayang. Unduhan memakai tautan bertanda tangan yang berlaku singkat, dan arsip tersimpan selama 7 hari.',
        ],
    ],
];
