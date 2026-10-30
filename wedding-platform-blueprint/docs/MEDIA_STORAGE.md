# Media Storage, Gallery & Archive

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
