# Media Storage, Gallery & Archive

> **Status implementasi (2026-10-30).** Alur di bagian 2 menjelaskan target presigned upload ke object storage + worker. Implementasi saat ini berbeda: unggahan dikirim ke aplikasi (adapter S3 belum terpasang), pemrosesan dan pembuatan arsip berjalan sinkron, dan build PHP di mesin ini hanya menghasilkan render PNG. Foto tanpa album tidak pernah tampil publik. Rincian keputusan dan konsekuensinya ada di `adr/009-media-storage-and-archive.md`; bukti test ada di `PHASE_5_7_REPORT.md`. Bagian lain dokumen ini tetap menjadi acuan kebijakan.

## 1. Prinsip

- Database menyimpan metadata; object storage menyimpan file.
- Original, preview, dan archive dipisahkan secara logis.
- Storage quota adalah bagian dari entitlement.
- Upload dan archive generation berjalan asinkron bila perlu.
- Akses file ditentukan server-side.
- Retensi harus transparan dan dapat diaudit.

## 2. Upload flow

1. Client meminta upload intent.
2. Server autentikasi dan memverifikasi hak akses.
3. Server memeriksa batas ukuran, jenis media, jumlah, dan kuota.
4. Server membuat object key acak dan signed upload URL.
5. Client upload langsung ke object storage.
6. Client mengirim completion request.
7. Backend memverifikasi object, size, dan metadata.
8. Worker memproses file.
9. File berstatus ready setelah pemeriksaan selesai.
10. UI menampilkan hasil atau error yang dapat ditindaklanjuti.

Gunakan reservasi kuota atau mekanisme atomik untuk mencegah dua upload bersamaan melampaui batas.

## 3. File policy

- Tetapkan format yang didukung.
- Tetapkan batas ukuran per file dan per batch.
- Periksa MIME dan signature.
- Sanitasi filename hanya untuk display.
- Gunakan object key yang tidak berasal langsung dari nama file.
- Jangan publikasikan file sebelum status ready.
- Hindari metadata EXIF sensitif jika tidak dibutuhkan; tentukan apakah EXIF akan dipertahankan atau dihapus.
- Beri batas resolusi untuk mengendalikan biaya pemrosesan.
- Gunakan antivirus/content scanning bila sesuai kemampuan provider dan risiko.

## 4. Gallery

- Album terkait wedding.
- Visibility: private, public, atau token-access sesuai kebutuhan.
- Jangan menganggap URL sulit ditebak sebagai kontrol akses.
- Foto tamu yang diunggah harus memiliki aturan persetujuan/moderasi.
- Pemilik dapat menghapus media sesuai kebijakan.
- Catat uploader dan waktu upload tanpa mengumpulkan data berlebihan.

## 5. Download

- Download individual menggunakan signed URL berumur pendek.
- Download massal menggunakan archive job.
- ZIP memiliki batas ukuran/jumlah file dan masa berlaku.
- Link download tidak ditulis ke log.
- Bersihkan output sementara setelah expiry.
- Retry job secara aman.
- UI menampilkan status queued, processing, ready, failed, expired.

## 6. Archive lifecycle

- Buat arsip dari file yang memang termasuk dalam entitlement.
- Simpan manifest internal agar isi arsip dapat diaudit.
- Pastikan proses tidak memasukkan file milik acara lain.
- Jika ada file yang dihapus selama job berlangsung, tentukan perilaku yang konsisten.
- Batasi jumlah archive job per user/wedding.
- Berikan peringatan saat masa arsip mendekati akhir.
- Retensi archive berbeda dari masa publikasi undangan.

## 7. Cost controls

Pantau:
- Bytes original.
- Bytes preview.
- Bytes archive.
- Egress/bandwidth.
- Request count.
- Transformations.
- Failed uploads.
- Orphan objects.
- Storage per wedding dan per plan.

Tentukan lifecycle policy provider untuk file sementara, tetapi jangan biarkan lifecycle provider bertentangan dengan retensi yang dijanjikan kepada pelanggan.

## 8. Reconciliation

Job berkala:
- Bandingkan object storage dengan metadata.
- Temukan orphan object.
- Temukan metadata yang menunjuk object hilang.
- Perbarui storage usage.
- Laporkan kegagalan.
- Jangan langsung menghapus orphan tanpa masa karantina dan verifikasi.

## 9. Acceptance tests

- Pengguna tidak dapat upload ke wedding lain.
- Upload yang melebihi kuota ditolak.
- File palsu/format tidak didukung ditolak.
- File pending tidak tampil publik.
- Signed URL expired tidak bisa digunakan setelah masa berlaku.
- ZIP hanya berisi media yang berhak diakses.
- Job gagal dapat diulang tanpa membuat arsip tak terkendali.
- Retensi tidak menghapus file sebelum waktunya.

## 10. Batas unggahan dan konfigurasi server

PHP dapat menolak unggahan sebelum aplikasi berjalan: badan request yang melebihi `post_max_size` dihentikan dengan `PostTooLargeException` (HTTP 413), dan satu berkas yang melebihi `upload_max_filesize` dibuang tanpa pesan. Karena itu batas yang ditampilkan ke pelanggan tidak boleh berasal dari config saja.

- `App\Support\Media\UploadLimits` menghitung batas efektif = nilai terendah antara `config/platform.php` (`MEDIA_MAX_FILE_KB`, `MEDIA_MAX_UPLOAD_KB`, `MEDIA_MAX_FILES_PER_UPLOAD`) dan `upload_max_filesize`/`post_max_size` milik host, dengan margin 512 KB di bawah `post_max_size` untuk boundary multipart dan field formulir.
- `StoreMediaRequest` memakai angka yang sama untuk aturan `max:` per berkas dan untuk pemeriksaan total ukuran satu kirim, sehingga pesan validasi menyebut angka yang benar.
- Halaman galeri menampilkan batas efektif dan memberi catatan bahwa angkanya mengikuti server bila PHP lebih ketat; formulir membawa batas tersebut (`data-max-file-bytes`, `data-max-total-bytes`) agar `resources/js/app.js` dapat menolak kombinasi yang terlalu besar sebelum dikirim dan menyebut foto mana yang harus dibuang.
- Permintaan yang tetap terlalu besar dijawab `resources/views/errors/413.blade.php`: halaman mandiri tanpa sesi maupun layout, memuat batas yang berlaku, saran memecah unggahan, dan tautan kembali. Setiap kejadian dicatat sebagai `request.post_too_large` beserta `content_length`, `post_max_size`, dan `upload_max_filesize`.
- `MEDIA_RESPECT_PHP_LIMITS=false` hanya untuk pengujian; produksi harus membiarkannya `true` agar formulir tidak menjanjikan ukuran yang akan ditolak host.

Nilai server yang disarankan (sesuaikan dengan paket terbesar):

```ini
; php.ini (FPM dan CLI)
upload_max_filesize = 16M
post_max_size = 48M
memory_limit = 256M
max_file_uploads = 20
max_execution_time = 120
```

```nginx
# nginx: harus >= post_max_size
client_max_body_size 48m;
```

- Batas per foto 8 MB dengan 10 foto satu kirim membutuhkan ruang ±80 MB; bila host tidak dapat menyediakan, turunkan `MEDIA_MAX_UPLOAD_KB` (mis. 24 MB) dan biarkan pelanggan mengunggah beberapa kali.
- Pemrosesan GD berjalan sinkron; `memory_limit` minimal 256 MB disarankan untuk foto 12 MP.

Saat badan request melebihi `post_max_size`, PHP sudah mencetak warning `POST Content-Length of ... exceeds the limit ...` pada request startup — sebelum Laravel berjalan. Dengan `display_errors=Off` (default produksi) warning itu hanya masuk log dan halaman 413 terkirim dengan status yang benar. Bila `display_errors` menyala (umum di server pengembangan), warning tersebut dikirim lebih dulu, header terlanjur terkirim, dan halaman yang sama tiba sebagai status 200 dengan warning di atasnya; isi halaman tetap benar, jadi angkat ke produksi dengan `display_errors=Off`.

Pengujian tambahan pada bagian 9:

- Foto di atas batas efektif ditolak dengan pesan yang menyebut angka batas.
- Gabungan beberapa foto melebihi batas satu kirim ditolak sebelum ada baris `media_assets` dibuat.
- Halaman galeri menampilkan batas efektif dan formulir membawa batas yang sama untuk pemeriksaan di browser.
- Halaman 413 dapat dirender tanpa sesi dan menyebut batas per foto, jumlah, dan total.
