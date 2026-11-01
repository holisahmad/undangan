# UI/UX Flows

## 1. Website marketing

Flow:
1. Landing page: hero, angka nyata, grid fitur, strip contoh tema (terkurasi), blok layanan pelaminan, harga, tiga langkah, testimoni, FAQ, kontak.
2. Jelajahi template di `/tema`: filter kategori + paket, grid kartu, lalu "Tampilkan lebih banyak tema" (halaman berikutnya; tanpa JavaScript menjadi tautan biasa).
3. Buka detail dan preview — setiap kartu menautkan halaman contoh yang memakai data terbit yang sama dengan yang dilihat tamu.
4. Bandingkan paket; kartu tema eksklusif menyebut paket pembukanya, bukan sekadar bertanda "premium".
5. Tanya layanan non-undangan lewat kartu mitra → form kontak dengan minat layanan yang sudah terisi (`?layanan=`).
6. Daftar/login.
7. Buat draft atau checkout sesuai strategi produk.
8. Selesaikan pembayaran.
9. Kembali ke dashboard.

CTA utama harus konsisten. Jangan memaksa login sebelum pengguna dapat menilai contoh desain jika preview publik aman untuk ditampilkan.

Identitas merek pada alur ini:

- Header menampilkan emblem berdampingan dengan nama merek; footer menampilkan tagline dan kredit pembangun. Sumbernya `APP_NAME`, `config('site.tagline')`, dan `config('site.credit')`, bukan teks di dalam view.
- Setiap halaman pemasaran membawa ikon, manifest, `theme-color`, dan `og:image` dari `partials/brand-head.blade.php`, sehingga tautan yang dikirim lewat WhatsApp tampil dengan identitas yang sama (lihat `adr/013-brand-identity-and-icon-set.md`).
- Navigasi dan footer menambah tautan `Tema` (katalog) dan `Layanan`; tautan `Layanan` hanya muncul bila `config('site.services')` masih berisi mitra, dan jam promo hanya muncul bila `site.promo.ends_at` punya tenggat nyata (`adr/014-theme-catalogue-and-package-tiers.md`, `adr/015-landing-services-and-honest-numbers.md`).

## 2. Pembuatan undangan

Wizard:
1. Pilih template — pada halaman pembuatan seluruh tema yang terbit ditawarkan (belum ada yang dibayar), lengkap dengan badge, kategori, dan label paket pembuka tema eksklusif.
2. Isi data mempelai.
3. Tambahkan acara (akad, resepsi, atau acara lain).
4. Unggah foto.
5. Isi cerita/teks opsional.
6. Atur fitur.
7. Preview mobile/desktop.
8. Validasi kelengkapan.
9. Checkout bila belum memiliki entitlement.
10. Publish.
11. Bagikan tautan.

Persyaratan:
- Draft dapat dilanjutkan nanti.
- Field wajib ditandai jelas.
- Error tidak menghapus input yang sudah valid.
- Perubahan draft tidak mengubah publikasi hingga publish dilakukan.
- Jika entitlement tidak valid, jelaskan tindakan yang dapat dilakukan.
- Tema di atas plafon paket pemilik tetap tampil di pemilih editor, tetapi non-aktif dengan nama paket yang membukanya; draf yang sudah memakai tema itu tetap bisa disimpan, dan penolakan terjadi saat terbit dengan kalimat yang menyebut paket yang harus dibeli (ADR-014).

## 3. Tamu membuka undangan

1. Membuka URL.
2. Melihat pembuka dan nama penerima jika token valid.
3. Membaca detail acara.
4. Membuka peta atau kalender.
5. Mengisi RSVP jika diaktifkan.
6. Mengirim ucapan jika diaktifkan.

Jangan meminta pembuatan akun tamu untuk fungsi dasar. Tampilkan pesan yang jelas jika undangan expired atau token dicabut.

## 4. Dashboard pelanggan

Ringkasan:
- Tanggal acara.
- Status publikasi.
- Kelengkapan data.
- Status pembayaran.
- Sisa masa aktif.
- Ringkasan RSVP jika tersedia.
- Status galeri dan arsip jika dibeli.

Navigasi diprioritaskan berdasarkan tugas. Fitur yang tidak tersedia di paket dapat ditampilkan secara tidak mengganggu dengan penjelasan upgrade yang transparan.

## 5. Admin workflow

### Pesanan
- Lihat pesanan.
- Periksa status transaksi terverifikasi.
- Tangani exception melalui prosedur yang tercatat.
- Jangan mengubah status pembayaran hanya berdasarkan bukti screenshot pelanggan.

### Template
- Buat/update template.
- Validasi schema dan fitur.
- Preview.
- Publikasikan versi.
- Periksa dampak pada undangan lama.

### Kedaluwarsa
- Sistem memberi peringatan sesuai kebijakan.
- Pelanggan dapat memperpanjang atau mengekspor data jika diizinkan.
- Sistem menonaktifkan publikasi saat waktunya.
- Retensi berjalan terpisah.
- Hasil job tercatat.

## 6. Galeri dan arsip

1. Pemilik/admin berwenang membuat album.
2. Upload file dengan progress.
3. Sistem memvalidasi dan memproses file.
4. Foto tampil setelah siap dan lolos moderasi yang diperlukan.
5. Pelanggan memilih unduh satuan atau massal.
6. Sistem membuat archive job.
7. Pelanggan menerima link download terbatas waktu.
8. Sistem membersihkan ZIP sementara.
9. Masa akses/retensi ditangani sesuai kebijakan.

## 7. UX untuk masa aktif

- Tampilkan tanggal berakhir dengan zona waktu yang jelas.
- Kirim notifikasi pada jadwal yang dikonfigurasi.
- Jelaskan perbedaan antara halaman publik berakhir dan file dihapus.
- Beri CTA untuk memperpanjang atau mengunduh data.
- Jangan membuat ancaman atau countdown manipulatif.
- Jangan menjanjikan file tersedia setelah tanggal tertentu jika proses retensi belum dapat menjaminnya.

## 8. Responsive QA

Uji:
- 320 px lebar.
- 375–390 px lebar.
- Tablet.
- Desktop.
- Koneksi lambat.
- Nama dan alamat panjang.
- Tidak ada foto.
- Foto gagal dimuat.
- Font gagal dimuat.
- Browser in-app dari aplikasi chat.
