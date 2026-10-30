# Product Requirements Document (PRD)

## 1. Ringkasan produk

Wedding Experience Platform adalah aplikasi web yang memungkinkan pelanggan membuat undangan pernikahan personal dari template yang tersedia, membagikannya kepada tamu, mengelola RSVP, dan mengakses galeri serta arsip foto sesuai paket dan kebijakan retensi.

## 2. Masalah yang diselesaikan

- Membuat undangan secara manual memakan waktu dan sulit diperbarui.
- Pemilik acara kesulitan mengelola daftar tamu dan RSVP.
- Informasi undangan tersebar di beberapa kanal.
- Foto pasca-acara sering tidak memiliki tempat unduh dan penyimpanan yang jelas.
- Operator bisnis membutuhkan panel untuk mengelola pelanggan, transaksi, template, masa aktif, dan dukungan.

## 3. Sasaran pengguna

### Primary: pasangan/pemilik acara
Menginginkan undangan elegan, mudah dibuat, mobile-friendly, mudah dibagikan, dan tidak memerlukan keterampilan teknis.

### Secondary: tamu
Menginginkan akses cepat ke informasi acara, lokasi, RSVP, serta galeri yang diizinkan.

### Internal: admin/operasional
Menginginkan kontrol atas pesanan, pembayaran, publikasi, masa aktif, media, dukungan, dan audit.

### Future: mitra
Wedding organizer dan fotografer yang dapat mengelola banyak acara. Bukan kewajiban MVP, tetapi model data tidak boleh mengunci produk hanya untuk satu acara per pengguna.

## 4. Prinsip produk

1. Mobile-first, cepat, dan mudah dipahami.
2. Template berkualitas lebih penting daripada kuantitas template.
3. Data acara dipisahkan dari layout template.
4. Draft tidak mengubah publikasi aktif sampai pengguna menerbitkan perubahan.
5. Masa aktif undangan, akses galeri, dan retensi arsip dipisahkan.
6. Keamanan dan privasi adalah fitur inti.
7. Fitur berbayar harus memiliki entitlement dan batas yang dapat diperiksa di server.
8. Admin mengerjakan tugas operasional melalui UI, bukan SQL manual.
9. Tidak menjanjikan fitur, pengiriman pesan, atau penyimpanan yang belum tersedia.
10. Pengembangan bertahap berdasarkan data penggunaan dan biaya nyata.

## 5. Ruang lingkup MVP

### Must have
- Registrasi, login, logout, reset password.
- Dashboard pelanggan.
- Membuat dan mengedit satu atau lebih proyek acara sesuai model data.
- Data mempelai dan satu atau lebih rangkaian acara.
- Tiga sampai lima template yang responsif.
- Upload foto dasar dengan validasi.
- Preview undangan.
- Draft dan publish.
- URL undangan unik.
- Status publikasi dan tanggal kedaluwarsa.
- Landing page bisnis, katalog template, detail template, dan paket harga.
- Admin panel dasar: pelanggan, acara/undangan, template, pesanan, status layanan.
- Paket dan pesanan.
- Pembayaran melalui provider yang dipilih; mode sandbox sebelum produksi.
- Share link melalui WhatsApp/web share, tanpa mengklaim status terkirim/dibaca.
- Logging, audit untuk tindakan sensitif, backup, dan error monitoring.
- Kebijakan privasi, syarat layanan, masa aktif, retensi, dan penghapusan.

### Should have setelah core stabil
- Daftar tamu, impor CSV, tautan personal, RSVP, ucapan.
- Statistik kunjungan dasar.
- Perpanjangan masa aktif.
- Notifikasi kedaluwarsa.
- Galeri dan unduhan foto.
- Arsip dan unduh massal.

### Later
- Kontribusi foto dari tamu.
- Integrasi WhatsApp Business Platform untuk pengiriman otomatis.
- Custom domain.
- Program referral/afiliasi.
- Portal mitra B2B.
- Analitik lanjutan.
- Multi-bahasa.
- Paket white-label.

## 6. Non-goals MVP

- Microservices.
- Native mobile app.
- AI-generated wedding copy sebagai ketergantungan inti.
- Otomasi pengiriman WhatsApp tanpa provider dan persetujuan yang sesuai.
- Penyimpanan foto permanen tanpa batas.
- Editor drag-and-drop bebas yang membuat layout tidak terkendali.
- Marketplace vendor lengkap.
- Multi-tenant B2B penuh sebelum ada bukti permintaan.

## 7. Fitur fungsional

### 7.1 Akun dan akses
- Pengguna dapat mendaftar dan memulihkan akun.
- Semua endpoint memverifikasi autentikasi dan otorisasi.
- Pemilik dapat mengundang kolaborator hanya bila fitur tersebut diaktifkan.
- Admin menggunakan role-based access control (RBAC).

### 7.2 Editor undangan
- Pilih template aktif.
- Isi data mempelai, acara, cerita, foto, dan pengaturan fitur.
- Simpan draft.
- Preview pada viewport mobile dan desktop.
- Publish membuat snapshot/version yang dapat dipulihkan.
- Validasi field wajib sebelum publish.

### 7.3 Halaman publik
- Menampilkan data publik yang disetujui pemilik.
- Responsif dan cepat.
- Mendukung metadata berbagi sosial yang tidak membocorkan data privat.
- Halaman expired tidak menampilkan undangan aktif; berikan pesan yang jelas.
- Tidak menampilkan nomor telepon tamu atau daftar tamu.

### 7.4 Guest management (fase setelah core)
- Buat/edit/import/export tamu.
- Grup tamu.
- Token undangan personal yang dapat dicabut.
- RSVP dan jumlah peserta yang diizinkan.
- Ucapan dengan moderasi/pelaporan.

### 7.5 Media
- Validasi file, ukuran, kuota, dan akses.
- Simpan file di object storage, metadata di database.
- Buat thumbnail melalui worker.
- Unduh satu file dan, setelah tersedia, arsip ZIP sementara.
- Patuhi masa akses dan retensi yang ditampilkan kepada pelanggan.

### 7.6 Admin
- Cari dan filter pelanggan, acara, pesanan, template, media.
- Atur paket, status template, masa aktif sesuai kewenangan.
- Lihat status penyimpanan dan pekerjaan gagal.
- Semua tindakan sensitif dicatat dalam audit log.

## 8. Non-functional requirements

- Keamanan: validasi server-side, otorisasi per objek, proteksi CSRF sesuai arsitektur, rate limiting, secure session, dan secret management.
- Performa: gambar responsif, lazy loading, cache yang aman, dan target Core Web Vitals yang diukur.
- Reliabilitas: retry terkontrol untuk job; operasi idempotent untuk webhook dan publikasi.
- Aksesibilitas: navigasi keyboard, label formulir, kontras, focus state, dan alt text.
- Observability: log terstruktur, error tracking, metrik job dan storage.
- Portabilitas: migrasi database, backup, dan prosedur restore terdokumentasi.
- Privasi: minimisasi data, pembatasan akses, retensi, dan prosedur permintaan subjek data.

## 9. KPI awal

Ukur, jangan mengarang angka hasil:
- Visitor → template preview.
- Preview → checkout.
- Checkout → paid.
- Paid → published.
- Median waktu dari pembayaran ke publikasi.
- Ticket support per 100 order.
- Attach rate add-on.
- Biaya storage dan egress per acara.
- Persentase job gagal.
- Retention/referral pelanggan.

Target numerik ditentukan setelah data baseline dari pilot tersedia.

## 10. Acceptance criteria produk

- Pelanggan dapat menyelesaikan pembuatan dan publikasi undangan tanpa akses developer.
- Draft tidak mengganti publikasi sebelum tindakan publish.
- Pengguna A tidak dapat membaca/mengubah data pengguna B.
- Transaksi tidak mengaktifkan layanan hanya berdasarkan redirect frontend.
- Expired invitation tidak terlihat sebagai undangan aktif.
- File tidak dapat diunduh tanpa hak akses yang sah.
- Pemberitahuan dan kebijakan retensi sesuai perilaku sistem.
- Alur utama memiliki automated tests.
