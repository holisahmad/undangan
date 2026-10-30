# UI/UX Flows

## 1. Website marketing

Flow:
1. Landing page.
2. Jelajahi template.
3. Buka detail dan preview.
4. Bandingkan paket.
5. Daftar/login.
6. Buat draft atau checkout sesuai strategi produk.
7. Selesaikan pembayaran.
8. Kembali ke dashboard.

CTA utama harus konsisten. Jangan memaksa login sebelum pengguna dapat menilai contoh desain jika preview publik aman untuk ditampilkan.

## 2. Pembuatan undangan

Wizard:
1. Pilih template.
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
