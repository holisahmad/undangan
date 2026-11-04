# Audit Tema & Refactor Lapisan Desain — Wedly

Tanggal: 4 November 2026
Ruang lingkup: lapisan tema undangan (`web/resources/views/invitations`, katalog `/tema`, kartu tema di
form pembuatan undangan, RSVP) dan temuan non-tema yang memengaruhi tampilan produksi.
Acuan rasa desain: `wedly.id/koleksi-desain` dan `inviee.id` (alur sampul → isi → RSVP yang tenang,
tipografi berjarak lebar, kontras rendah, transisi halus, tanpa ornamen berlebih).

Status: **perubahan sudah diterapkan, teruji lokal, dan di-commit/push.** Repo auto-deploy ke
Fly.io lewat GitHub, jadi `wedly.fly.dev` ikut terbarui setelah push.

---

## 1. Ringkasan

Masalah utamanya bukan "kurang banyak ornamen", tetapi **tidak adanya satu lapisan tema**:
tiga belas renderer menyalin blok CSS yang sama, memakai foto contoh hardcoded, dan katalog
menampilkan satu tata letak mini yang sama untuk semua desain. Akibatnya semua tema terasa
seperti satu template dengan warna berbeda — persis keluhan "kurang elegan".

Yang dikerjakan: satu fondasi tema berbasis token, resolver foto sungguhan, RSVP yang mengikuti
palet tema, dan kartu katalog yang menggambar miniatur berbeda per desain.

---

## 2. Temuan audit

### 2.1 Duplikasi CSS membuat desain kehilangan identitas (paling besar)

- 11 dari 13 renderer memuat blok `/* Visual Enhancements */` **identik, 2.956 karakter**
  (`classic-ivory`, `garden-botanical`, `modern-editorial`, `glasshouse-minimal`, `batik-pusaka`,
  `deco-noir`, `pesisir-senja`, `songket-empress`, `terracotta-sunset`, `sage-keepsake`;
  `velvet-royal` versi 2.899 karakter). Total ±35 KB CSS duplikat.
- Blok itu memaksa satu bentuk bingkai foto lengkung, satu bayangan hitam tebal, dan satu
  **animasi denyut `pulse-open`** ke semua desain — termasuk `glasshouse-minimal` yang konsepnya
  ruang putih lapang dan `deco-noir` yang geometris. Itulah sebabnya tema terasa "generik".
- `.cover__guest-card`, `.gallery-grid`, `.gallery-item`, `.reveal-on-scroll` juga diduplikasi;
  setiap perubahan harus disalin 11–12 kali, dan mulai menyimpang di `velvet-royal`.

### 2.2 Undangan sungguhan menampilkan foto stok

- Semua renderer meng-hardcode `asset('images/demo/couple-hero.webp')`, `bride.webp`, `groom.webp`,
  dan `gallery-1..4.webp` — termasuk di halaman undangan terbit, bukan hanya halaman contoh.
- Konsekuensi: foto milik pasangan **tidak pernah muncul** di undangan mereka, dan tamu melihat
  wajah pasangan demo pada undangan orang lain. Ini masalah kepercayaan, bukan hanya estetika.
- Bagian galeri tetap dirender walau tidak ada foto, sehingga muncul grid kosong (atau foto stok).

### 2.3 Katalog `/tema` menampilkan 13 kartu yang sama

- `TemplatePresentation::cardTheme()` hanya menyediakan `frame/accent/font/swatch`, dan
  `template-card.blade.php` menggambar satu markup mini yang sama: label "The Wedding Of",
  "Ayu & Bima", garis, tanggal. Yang berubah hanya gradien 1,5 px di atas kartu.
- Semua nama memakai Playfair Display, meski renderer aslinya memakai Cinzel, Cormorant, atau
  Great Vibes. Palet kartu juga menyimpang dari renderer (mis. Emerald Gilded tampil sebagai
  gradien rose-emas, bukan hijau zamrud).
- Badge, chip kategori/paket, dan dua tombol di area hover membuat kartu ramai dibanding
  referensi (`wedly.id/koleksi-desain` memakai satu bingkai tenang + satu aksi).

### 2.4 RSVP bertabrakan dengan setiap palet

- `extras.blade.php` menulis CSS sendiri: latar putih, tombol burgundy `#9f1239`, font sans,
  radius 10 px — dirender apa adanya di dalam Emerald Gilded, Midnight Celestial, Velvet Royal,
  dan Deco Noir. Formulir menjadi "kotak asing" di tengah undangan.

### 2.5 Reveal-on-scroll bisa menyembunyikan konten

- `.reveal-on-scroll { opacity: 0 }` didefinisikan tanpa syarat, sementara `is-revealed` baru
  ditambahkan oleh `public/js/invitation.js`. Bila berkas itu gagal dimuat atau diblokir CSP,
  bagian galeri tetap tak terlihat. CSS kini digerbangi `html.js` yang dipasang oleh skrip itu.

### 2.6 Pelanggaran CSP di kartu hadiah

- `power-gift.blade.php` memakai atribut inline `onerror="this.style.display='none'"`.
  Kebijakan `script-src 'self'` memblokir event handler inline, jadi atribut itu tidak pernah
  berfungsi; sudah dihapus.

### 2.7 Pita demo berwarna burgundy

- `demo-bar.blade.php` memakai burgundy brand di atas semua tema. Diganti bar ink netral yang
  tidak bersaing dengan palet undangan di bawahnya.

### 2.8 Catatan non-tema (belum diubah, perlu keputusan)

- **Media di disk lokal pada Fly.io.** `storage/app/media` berada di filesystem container.
  Fly mengganti container saat deploy/restart, sehingga foto galeri, bukti transfer, dan arsip
  ZIP dapat hilang. Database sudah aman di Supabase, media belum. Perbaikan yang disarankan:
  pasang adapter S3/R2 (`league/flysystem-aws-s3-v3`) dan arahkan disk media ke bucket.
- `docker-entrypoint.sh` menjalankan `migrate`, `db:seed`, dan `user:admin` pada container
  pertama, ditambah `release_command = migrate --force` di `fly.toml`. Seed ganda pada database
  produksi sebaiknya dipersempit ke seeder idempoten saja.
- Panel katalog menyebut "13 tema"; angka itu ditulis manual di beberapa tempat marketing.
  Sumber kebenaran tetap jumlah baris template terbit.

---

## 3. Perubahan yang diterapkan

### 3.1 Fondasi tema bersama

- **Baru:** `web/resources/views/invitations/partials/theme-base.blade.php`
  Satu sumber untuk bingkai foto, monogram, galeri, reveal, dan formulir RSVP. Semua membaca token
  `--pw-*` yang memang sudah dipasang tiap renderer (`--pw-accent`, `--pw-line`, `--pw-surface`,
  `--pw-radius`, `--pw-display`, `--pw-numeric`), sehingga desain tetap punya palet sendiri.
  Disisipkan lewat `head-meta.blade.php`, jadi ke-13 renderer otomatis memakainya.
- 12 renderer kehilangan blok CSS duplikat (±35 KB lebih ringan). `emerald-gilded` dan
  `midnight-celestial` tidak pernah punya blok itu, jadi tidak disentuh.
- `pulse-open` (denyut bayangan) dihapus dari semua desain; hover foto memakai satu transisi
  tenang `cubic-bezier(.16,1,.3,1)`.

### 3.2 Foto: hanya foto pasangan, atau monogram

- **Baru:** `web/app/Support/Invitations/InvitationMedia.php`
  - Halaman contoh tema memakai set demo (tetap ada, agar desain bisa dinilai).
  - Undangan sungguhan hanya memakai aset `ready` dari **album publik** milik pasangan itu,
    disajikan lewat signed URL 15 menit yang sama dengan galeri publik.
  - Tanpa foto, kembalian `null` dan renderer menampilkan monogram.
- **Baru:** `invitations/partials/photo.blade.php` (foto atau monogram, satu markup) dan
  `invitations/partials/gallery.blade.php` (galeri hanya dirender bila ada foto).
- `PublicInvitationController` memakai kueri galeri yang sama untuk foto dan untuk keputusan
  menampilkan tautan galeri; anggaran kueri halaman undangan **turun dari 8 ke 7**.

### 3.3 Katalog dengan arah seni per tema

- `TemplatePresentation::card()` kini mengembalikan `cover, paper, ink, accent, line, display,
  script` — palet dan tipografi diambil dari renderer aslinya.
- **Baru:** `templates/partials/mini-cover.blade.php` menggambar 10 varian miniatur:
  `frame`, `editorial`, `botanical`, `gilt`, `keepsake`, `heritage`, `celestial`, `sunset`,
  `window`, `coastal`, `songket`, `royal`, `deco` — semuanya CSS/SVG, tanpa berkas gambar.
- `template-card.blade.php` ditulis ulang: kepingan kaca tenang, label kategori·paket kecil,
  nama serif, satu baris deskripsi, satu tautan "Lihat contoh", satu tautan "Gunakan".
  Badge menjadi penanda kecil, bukan pil mencolok.
- Gaya kartu ada di `resources/css/app.css` (blok `@layer components` baru) dan memakai custom
  property inline, jadi Tailwind tetap bisa memindai seluruh kelas.

### 3.4 Tema lain

- RSVP dan pesan status/galat kini mengikuti palet, radius, dan tipografi tema.
- Pita demo menjadi bar ink netral.
- `invitation.js` menandai `html.js` sebelum animasi diaktifkan (tanpa JS, konten tetap terlihat).
- Kartu pemilih tema di form "Buat undangan" memakai `card()` yang sama, jadi katalog dan pemilih
  tidak bisa lagi menyimpang satu sama lain.

### 3.5 Foto pre-wedding milik pasangan

- **Baru:** panel "Foto pre-wedding" di editor undangan (`weddings.edit`). Pasangan mengunggah
  JPG/PNG/WebP langsung dari sana; foto masuk album publik `Prewedding`, album yang sama yang
  dibaca `InvitationMedia` dan halaman galeri, jadi hanya ada satu sumber kebenaran.
- Unggahan pertama otomatis menjadi **sampul**; setiap foto punya tombol "Jadikan sampul".
- Kolom baru `weddings.photo_display` (`prewedding` | `monogram`) memberi opsi **tanpa foto**: undangan
  memakai monogram inisial mempelai, foto tetap tersimpan dan bisa diaktifkan lagi kapan saja.
- `weddings.cover_media_asset_id` menyimpan pilihan sampul; foto sampul diurutkan lebih dulu di
  kueri yang sama, sehingga anggaran kueri halaman undangan tetap **7**.
- Menghapus foto sampul otomatis mengosongkan penunjuk sampul, dan hapus foto kini menolak aset milik
  undangan lain (sebelumnya hanya mengandalkan UUID pada route).
- Untuk tamu: undangan personal kini mengirim `$media` yang sudah dihitung, sama seperti halaman publik.

---

## 4. Verifikasi yang dijalankan

| Pemeriksaan | Hasil |
| --- | --- |
| `php vendor/bin/pint --quiet` | lulus |
| `npm run build` (Vite + Tailwind) | lulus, CSS 90,5 kB (sebelumnya 92,2 kB) |
| `php artisan test` | **315 lulus, 1 skip** (2.912 asersi) |
| `QueryBudgetTest` halaman undangan publik | 7 kueri (batas 8; sebelumnya 8) |
| Halaman `/tema` lokal | 200, kartu miniatur per tema |
| Halaman contoh `/undangan/contoh/{slug}` | 200 untuk seluruh 13 tema lewat suite test |

Belum dijalankan: uji visual di perangkat fisik, pembandingan screenshot lintas browser, dan
verifikasi produksi `wedly.fly.dev` (menunggu commit + deploy).

---

## 5. Rekomendasi lanjutan

1. **Sampul untuk lima tema Essential** (`classic-ivory`, `modern-editorial`, `garden-botanical`,
   `pisces`/`pesisir-senja`, `glasshouse-minimal`): saat ini langsung masuk ke isi, sementara tema
   Signature/Premium punya gerbang sampul. Menyamakan alur akan membuat pengalaman "buka undangan"
   konsisten dan terasa lebih mahal.
2. **Selesai — unggah foto mempelai dari editor**, termasuk pilihan sampul dan mode monogram.
   Langkah berikutnya bila diinginkan: pemilihan potret mempelai pria/wanita secara terpisah.
3. **Pindahkan media ke object storage** sebelum mengandalkan galeri di produksi Fly. Disk `media`
   masih lokal di container, jadi foto unggahan hilang saat redeploy sampai `MEDIA_DISK` diarahkan
   ke bucket S3/R2 (butuh paket `league/flysystem-aws-s3-v3` + kredensial bucket).
4. **Font lokal + subset** (Cormorant, Cinzel, Great Vibes, Playfair, Plus Jakarta) untuk
   menghilangkan permintaan ke Google Fonts dan mencegah FOUT pada 3G.
5. **Enforce CSP** setelah semua inline style dibereskan, dan tambahkan uji snapshot HTML untuk
   tiap renderer agar regresi tata letak ketahuan sebelum publish.
6. **Turunkan jumlah tema per kategori di katalog** bila nanti bertambah banyak: filter saat ini
   menampilkan semua chip sekaligus; referensi memakai tab kategori → sub-kategori.
