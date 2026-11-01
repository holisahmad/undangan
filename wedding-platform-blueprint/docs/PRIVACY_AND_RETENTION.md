# Privacy, Lifecycle, Retention & Deletion

## 1. Prinsip

Masa aktif undangan, masa akses galeri, dan retensi file adalah tiga kebijakan berbeda. Jangan menyatukannya ke satu tanggal expiry.

## 2. Jenis siklus hidup

### Invitation publication
Mengatur apakah halaman undangan tampil publik.

### Gallery access
Mengatur apakah pelanggan/tamu yang berwenang dapat melihat atau mengunduh galeri.

### Media retention
Mengatur berapa lama original, preview, dan arsip disimpan sebelum dihapus sesuai kebijakan.

### Account/order retention
Mengatur penyimpanan data akun dan transaksi sesuai kebutuhan layanan serta kewajiban hukum.

### Leads and aggregate analytics
Lead dari formulir kontak menyimpan nama, email, WhatsApp, pesan, sumber, minat layanan (`service_interest` — hanya slug dari daftar `config('site.services')`, bukan teks bebas), dan atribusi UTM first-touch. Tidak ada IP, user agent, atau riwayat kunjungan. Tabel `analytics_events` hanya berisi nama event, `wedding_id` opsional, properti non-personal, dan waktu, sehingga funnel tidak dapat ditelusuri ke individu.

## 3. Contoh kebijakan awal untuk divalidasi

- Undangan aktif 12 bulan sejak publikasi.
- Peringatan 30 hari dan 7 hari sebelum berakhir.
- Halaman publik dinonaktifkan saat entitlement berakhir jika tidak diperpanjang.
- Galeri dapat memiliki masa akses berbeda, misalnya 30 atau 90 hari setelah galeri diaktifkan.
- Arsip berbayar memiliki kapasitas dan masa retensi eksplisit.
- Setelah akses berakhir, sediakan periode ekspor/pemulihan yang jelas sebelum penghapusan sesuai syarat.
- Semua durasi harus configurable dan sesuai paket.

Angka ini hipotesis produk. Jangan meng-hardcode ke banyak bagian aplikasi.

## 4. Status

Pisahkan status publikasi dari status layanan dan pembayaran. Contoh status publikasi:
- draft
- published
- unpublished
- expired

Status entitlement dapat meliputi:
- pending
- active
- expiring
- expired
- revoked

Status media:
- pending_upload
- uploaded
- processing
- ready
- quarantined
- deletion_pending
- deleted
- failed

Hindari status yang saling bertentangan. Dokumentasikan transisi yang sah.

## 5. Notifikasi

- Jadwalkan berdasarkan `expires_at` dan timezone tampilan.
- Job harus idempotent agar retry tidak mengirim duplikat secara tidak terkendali.
- Catat channel, status, dan kegagalan pengiriman.
- Beri pelanggan informasi jelas tentang perbedaan halaman nonaktif dan penghapusan file.
- Jangan mengklaim notifikasi terkirim jika provider gagal.

## 6. Penghapusan

1. Identifikasi resource yang memenuhi syarat.
2. Periksa legal hold atau alasan sah untuk retensi bila relevan.
3. Tandai deletion pending.
4. Batasi akses publik/baru sesuai kebijakan.
5. Hapus object dari storage.
6. Verifikasi hasil atau catat kegagalan.
7. Bersihkan metadata yang memang harus dihapus.
8. Rekonsiliasi dengan object storage.
9. Hormati retensi backup dan dokumentasikan jadwalnya.

Jangan menghapus file secara irreversible sebelum pelanggan mendapatkan pemberitahuan dan kesempatan yang telah dijanjikan dalam kebijakan.

## 7. Data subject requests

Sediakan prosedur untuk permintaan akses, koreksi, ekspor, dan penghapusan data sesuai hukum yang berlaku. Verifikasi identitas dan otoritas pemohon sebelum tindakan. Catat pemenuhan permintaan tanpa menaruh data sensitif berlebihan di audit log.

Halaman `/kebijakan-privasi` menyebut penyelenggara layanan secara eksplisit, karena pemohon berhak tahu kepada siapa permintaan ditujukan. Identitas itu berasal dari `config/site.php` (`operator`, `address`) dan `.env` (`CONTACT_EMAIL`, `CONTACT_PHONE`); tidak ada satu pun alamat yang ditulis ulang di view. Mengganti badan usaha atau alamat berarti memperbarui kedua sumber itu sekaligus, bukan hanya satu halaman.

## 8. Public/private defaults

- Undangan dapat publik hanya jika pemilik memilih publish.
- Data tamu privat secara default.
- Nomor telepon tidak ditampilkan pada halaman publik.
- Media memiliki visibility yang jelas.
- Token undangan tidak memberi akses ke daftar tamu.
- Testimoni tayang hanya setelah pemilik mengirimkannya dan operator menyetujui; testimoni `pending`/`rejected` tidak pernah dirender di landing.
- Portofolio undangan bersifat opt-in: pemilik mengaktifkan sendiri, halaman memakai snapshot terbit, dan bilah penanda portofolio selalu terlihat. Ini keputusan pemilik, bukan default platform.
- Analitik agregat tidak menyimpan user id, IP, user agent, atau cookie pelacak.
- `noindex` bukan mekanisme autentikasi.
- Signed URL memiliki expiry singkat.

## 9. Backup

Jelaskan bahwa backup memiliki siklus retensi tersendiri. Data yang dihapus dari sistem utama mungkin bertahan pada backup sampai backup kedaluwarsa sesuai kebijakan. Restore harus mengikuti prosedur agar data yang seharusnya telah dihapus tidak kembali tanpa rekonsiliasi.
