# Business Model & Unit Economics

## 1. Positioning

Layanan undangan pernikahan digital yang menggabungkan undangan personal, pengelolaan tamu, galeri, dan opsi arsip kenangan. Diferensiasi harus dibuktikan lewat pengalaman pelanggan, kualitas desain, kemudahan operasional, dan layanan pasca-acara.

## 2. Segmen yang divalidasi

1. Pasangan yang membutuhkan undangan praktis dan terjangkau.
2. Pasangan yang mengutamakan desain dan personalisasi.
3. Wedding organizer/fotografer sebagai kanal kemitraan pada fase lanjutan.

Jangan menganggap semua segmen memiliki kebutuhan dan willingness-to-pay yang sama. Wawancarai calon pelanggan dan uji penawaran sebelum menetapkan harga final.

## 3. Model pendapatan

- Paket undangan sekali bayar.
- Desain template bertingkat sebagai pembeda paket: tema `essential` terbuka di semua paket, tema `signature` dan `premium` hanya dapat dipakai (dan diterbitkan) oleh paket yang membukanya — ditegakkan sistem, bukan hanya ditulis di tabel harga (ADR-014).
- Add-on RSVP/guest management jika tidak termasuk paket.
- Add-on galeri dan kapasitas storage.
- Perpanjangan masa aktif undangan.
- Perpanjangan akses galeri/arsip.
- Paket mitra dan referral di fase lanjutan. Sampai kontrak itu ada, layanan pelaminan (rias, dekorasi, dokumentasi, WO, cetak) ditampilkan di beranda sebagai kerja mitra tanpa harga dan tanpa tombol pesan; yang dihasilkan hanyalah lead yang sudah menyebut layanan yang ditanyakan (ADR-015).

## 4. Harga ilustratif untuk eksperimen

Angka berikut hanya hipotesis untuk validasi, bukan harga final:
- Essential: Rp99.000.
- Signature: Rp199.000.
- Premium: Rp349.000.
- Memory Archive: mulai Rp49.000, bergantung kapasitas dan durasi.

Jangan mempublikasikan harga sebelum batas fitur, biaya provider, pajak, refund, dan biaya support dipahami.

## 5. Unit economics

Catat per order:
- Pendapatan kotor.
- Diskon.
- Refund.
- Biaya payment gateway.
- Biaya variabel storage dan bandwidth.
- Biaya pemrosesan media.
- Biaya support variabel jika dapat dialokasikan.
- CAC dari kanal akuisisi.
- Contribution margin.

Rumus dasar:
- AOV = total nilai order / jumlah order.
- CAC = biaya akuisisi / pelanggan baru dari kanal tersebut.
- Conversion rate = jumlah konversi / jumlah pengunjung atau sesi yang ditetapkan.
- Attach rate = pelanggan yang membeli add-on / pelanggan yang memenuhi syarat.
- Contribution margin = pendapatan bersih dari order dikurangi biaya variabel terkait.

Jangan menghitung omzet sebagai laba. Biaya tenaga kerja, operasional, pajak, refund, dan pengembangan tetap perlu dihitung terpisah.

## 6. Kanal akuisisi

- SEO berbasis intent dan template. Halaman katalog `/tema` (difilter per kategori dan paket, berpaginasi, `CollectionPage` + `ItemList`) serta satu halaman contoh per tema adalah pintu masuk yang dapat diindeks; beranda hanya menampilkan strip terkurasi agar tidak memanjang setiap kali desain bertambah.
- Konten edukasi calon pengantin.
- Instagram/TikTok/Pinterest sesuai kemampuan produksi konten.
- Referral pelanggan.
- Kerja sama wedding organizer dan fotografer.
- Iklan berbayar setelah funnel checkout dan conversion tracking terbukti berfungsi.

## 7. Eksperimen awal

- Uji tiga variasi paket.
- Uji beberapa template dengan preview yang dapat dicoba.
- Wawancara pelanggan setelah pembelian dan setelah acara.
- Catat alasan batal checkout.
- Ukur kebutuhan support per pelanggan.
- Ukur biaya storage/egress dari data nyata sebelum menentukan kuota arsip.

## 8. Prinsip harga

- Paket harus mudah dibandingkan.
- Kuota dan masa aktif dinyatakan secara eksplisit.
- Tidak ada klaim “selamanya” kecuali bisnis dapat memenuhi janji itu secara berkelanjutan.
- Perpanjangan tidak boleh menghapus atau mengubah data secara diam-diam.
- Biaya tambahan harus dijelaskan sebelum pelanggan membayar.
- Tingkat template adalah batas pemakaian, bukan label harga: nama paket, harga, dan kuota tinggal di `plans`/`plan_features`, sedangkan `invitation_templates.tier` hanya menentukan desain mana yang boleh dipilih dan diterbitkan.
- Plafon desain dibekukan pada saat pesanan. Perubahan isi paket berikutnya tidak boleh mencabut tema yang sudah dibeli pelanggan, dan pemberian manual/support tanpa fitur beku dibaca sebagai plafon tertinggi.
- Tema yang belum terbuka tetap ditampilkan, bukan disembunyikan: katalog justru perlu menunjukkan apa yang belum dimiliki pelanggan, lengkap dengan paket pembukanya.
