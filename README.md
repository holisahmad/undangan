# Wedly — Wedding Invitation Platform

Nama merek (**Wedly**, tagline *Invitation · Celebration · Forever*) diambil dari `APP_NAME`; tagline dan kredit pembangun (Numos Studio) dari `config/site.php`. Produk dan usaha dipisah: **Wedly Studio** (`operator`) adalah pihak yang menerima pesanan dan tampil di blok kontak, halaman legal, serta `legalName` JSON-LD. Ikon, manifest, dan gambar berbagi dihasilkan oleh `tools/brand/build-icons.php` — lihat `adr/013-brand-identity-and-icon-set.md`.

Implementasi fase 1–7 dari blueprint di `wedding-platform-blueprint/`:

**auth → wedding → draf → publikasi → halaman publik**
**→ katalog template (13, bertingkat) + halaman /tema + panel admin dasar**
**→ order → pembayaran → entitlement otomatis**
**→ tamu → tautan personal → RSVP**
**→ galeri → arsip ZIP → retensi**
**→ hardening beta + share link + ringkasan RSVP**

Status: fase 1–5 selesai, fase 6 sebagian besar selesai, fase 7 hanya share link dan ringkasan RSVP yang dikerjakan; semuanya teruji lokal pada SQLite. Pembayaran memakai **transfer bank manual** (kode unik 3 digit + batas waktu bayar), media masih memakai disk lokal, dan item fase 7 yang bergantung pada bukti pelanggan ditunda. Seluruh antarmuka dirancang **mobile-first**. Rincian bukti dan risiko ada di `PHASE_5_7_REPORT.md`; keputusan media ada di `adr/009-media-storage-and-archive.md`, keputusan hardening/growth ada di `adr/010-beta-hardening-and-growth-scope.md`, alur pembayaran ada di `PAYMENT_FLOW.md`.

## Struktur

```
wedding-platform-blueprint/   Dokumen produk, arsitektur, schema, ADR (tidak di-commit, lihat .gitignore)
web/                          Aplikasi Laravel 13
  app/Actions/                Order, pembayaran, entitlement, publikasi, tamu/RSVP, media/arsip
  app/Contracts/Payments/     Kontrak adapter payment gateway
  app/Payments/               Gateway sandbox + registry (uji mekanik webhook)
  app/Support/Payments/       Transfer bank manual, jendela bayar, kode unik 3 digit
  app/Support/Guests/         Token hash, kuota tamu, parser & sanitizer CSV, ringkasan RSVP
  app/Support/Media/          Disk privat, kuota media, pemrosesan GD
  app/Http/Controllers/       Customer, Admin, tamu, undangan personal, galeri, webhook
  database/migrations/        Schema portabel (SQLite lokal, PostgreSQL produksi)
  app/Support/Billing/        Plafon tema per paket (TemplateAccess) + label fitur paket
  app/Support/Marketing/      Daftar layanan mitra untuk landing dan lead
  app/Support/Templates/      Registry renderer + kosakata kartu katalog
  database/seeders/           Template (13) + paket (3)
  resources/views/            Blade: dashboard, editor, publik, admin, pesanan, tamu, galeri
  tests/Feature/              Test auth, isolasi tenant, draf, publish, admin, pembayaran, tamu, media
tools/packagist-proxy.php     Utilitas dev untuk mirror Composer (lihat catatan)
tools/brand/build-icons.php   Generator favicon, ikon PWA, emblem, dan OG image dari logo PNG
docker-compose.yml            PostgreSQL 17 untuk parity/produksi
.github/workflows/ci.yml      CI: Pint, test SQLite + PostgreSQL 17, audit, build
```

## Kebutuhan

- PHP 8.3+ (diverifikasi pada 8.4.0) dengan ekstensi `pdo_sqlite`
- Composer 2
- Node.js 20+ dan npm (build aset)
- Opsional: Docker untuk PostgreSQL 17

## Menjalankan lokal

```powershell
cd web
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Buka `http://localhost:8000`. Pada environment `local`, seeder membuat akun demo `demo@undangan.test` dengan kata sandi `password`; akun ini tidak dibuat di produksi.

### Mobile-first

Semua layar ditulis untuk layar sempit lebih dulu: kelas Tailwind tanpa prefix adalah tampilan ponsel, `sm:`/`md:`/`lg:` hanya menambah ruang. Navigasi atas memakai menu `<details>` tanpa JavaScript di bawah breakpoint, tabel lebar dibungkus `overflow-x-auto`, daftar pesanan memakai kartu di ponsel, target sentuh ±44 px, dan input mendapat font 16 px di layar ≤640 px agar iOS tidak memperbesar halaman saat fokus. Aturan lengkapnya ada di `DESIGN_SYSTEM.md` bagian 4.1. Uji cepat: buka `http://localhost:8000` dengan DevTools pada lebar 360–414 px.

### Membeli paket dan aktivasi lewat transfer bank

1. Buka salah satu undangan → **Masa aktif** → **Pilih paket**.
2. Pilih paket (Essential/Signature/Premium). Pesanan dibuat dengan harga dari database, lalu sistem
   menetapkan **kode unik 3 digit** (100–999) dan **batas waktu bayar** (`PAYMENT_WINDOW_HOURS`, bawaan 24 jam).
3. **Lanjutkan pembayaran** → halaman `checkout/transfer/{order}` menampilkan daftar rekening dari
   `PAYMENT_BANK_ACCOUNTS`, nominal akhir (harga paket + kode unik), dan hitung mundur sisa waktu.
4. Pelanggan mentransfer sesuai nominal tersebut, lalu mengunggah bukti pada halaman yang sama
   (`POST orders/{order}/payment-confirmation`). Bukti disimpan di disk privat `PAYMENT_PROOF_DISK`
   dan hanya dapat diunduh operator. Setiap unggahan yang berhasil langsung mengirim email
   `PaymentProofSubmitted` ke `CONTACT_EMAIL` berisi pengirim, bank, nominal, tanggal transfer, nama
   berkas, catatan pelanggan, tautan ke panel pesanan, dan tombol balas ke pelanggan; kegagalan kirim
   email tidak membatalkan unggahan maupun menyembunyikan error di layar.
5. Operator memverifikasi di `/admin/orders` → detail pesanan → **Verifikasi pembayaran**. Hanya setelah
   itu order menjadi `paid` dan entitlement dibuat (idempotent, tercatat sebagai `order.paid`).

Order tidak punya status baru: bila jendela bayar lewat, order tetap `pending_payment` tetapi berlabel
**Kedaluwarsa** dan percobaan pembayarannya ditutup. Pelanggan dapat meminta kode unik baru
(`POST orders/{order}/payment-code`) yang sekaligus membuka jendela bayar baru.

Penutupan jendela dijalankan `php artisan payments:expire` setiap 5 menit dari `routes/console.php`;
job ini melewati order yang bukti transfernya sudah diunggah dan menunggu verifikasi.

Gateway sandbox masih ada untuk menguji mekanik webhook bertanda tangan (dipakai test), dan hanya
dapat diaktifkan pada environment non-produksi lewat `PAYMENT_GATEWAY=fake`:

```powershell
php artisan payments:simulate INV-20261030-ABC123
php artisan payments:simulate INV-20261030-ABC123 --status=failed
```

### Memberi masa aktif tanpa pembayaran

Untuk dukungan, operator masih dapat memberi masa aktif yang tercatat di audit log:

```powershell
php artisan entitlement:grant-trial {slug-atau-uuid} --months=12 --actor=admin@example.test
```

Menaikkan role akun menjadi admin (akses `/admin`):

```powershell
php artisan tinker --execute="App\Models\User::where('email','demo@undangan.test')->update(['role'=>'admin']);"
```

Tautan publik: `http://localhost:8000/u/{slug}`.

### Halaman publik, contoh template, dan discovery

- `GET /` menampilkan landing publik: hero, pita angka yang diturunkan dari katalog nyata, grid fitur, strip contoh tema (terkurasi, `platform.marketing.landing_template_limit`) dengan tautan "Lihat semua N tema", blok layanan pelaminan (kerja mitra), daftar harga dari katalog paket, tiga langkah, testimoni yang sudah disetujui, FAQ, kontak, dan formulir **Minta dihubungi**. Hitung mundur promo hanya muncul bila `site.promo.ends_at` memberi tenggat nyata. Halaman ini memuat meta description, Open Graph, dan JSON-LD (`Organization`, `WebSite`, `ItemList`, `FAQPage`).
- `GET /inspirasi` menampilkan portofolio undangan yang pemiliknya mengaktifkan showcase; `GET /inspirasi/{slug}` merender snapshot terbit dengan bilah penanda portofolio, boleh diindeks, dan tanpa formulir RSVP serta tanpa data tamu.
- `POST /kontak` menyimpan lead (nama, email, WhatsApp, pesan, opsi `service_interest`) beserta atribusi first-touch `utm_source`/`utm_medium`/`utm_campaign`. Minat layanan divalidasi terhadap `config('site.services')` lewat `App\Support\Marketing\WeddingServices`, bisa pra-terisi dari `?layanan={slug}` pada kartu layanan, dan tampil sebagai label manusiawi di antrean admin maupun email. Formulir memakai honeypot `website`, dibatasi 6 permintaan/menit per IP (`MARKETING_LEAD_RATE_LIMIT`), dan tidak menyimpan IP maupun user agent. Lead yang tersimpan langsung dikirim ke email usaha (`NewEnquiryReceived`) agar tidak menunggu ada yang membuka antrean admin.
- `GET /kebijakan-privasi` dan `GET /syarat-ketentuan` memuat kebijakan privasi dan ketentuan layanan; tautannya ada di footer setiap halaman publik.
- Kontak publik berasal dari `CONTACT_EMAIL`, `CONTACT_PHONE`, `CONTACT_WHATSAPP`, dan `CONTACT_MAPS_URL`; alamat pos dari `config/site.php` (`address`, disimpan sebagai bagian agar teks halaman dan schema.org membaca sumber yang sama). Blok yang sama dipakai footer pemasaran, kartu kontak beranda, halaman privasi/syarat, footer dashboard, dan halaman masuk; JSON-LD menambahkan `legalName`, `telephone`, `PostalAddress`, dan `hasMap`. Tautan WhatsApp selalu memakai bentuk `62…` lewat `App\Support\Contact\PhoneNumber` — `wa.me/0813…` ditolak WhatsApp. Bila WhatsApp kosong, tautannya tidak ditampilkan.
- Harga di landing dan halaman pesanan memakai katalog `plans`, dengan label fitur terpusat di `app/Support/Billing/PlanFeatureLabel.php`. Bila katalog kosong, bagian harga menampilkan ajakan menghubungi kami, bukan angka palsu.
- `GET /tema` adalah katalog tema publik yang dapat diindeks: filter `kategori` + `paket` dengan penghitung per pilihan, paginasi server (`platform.marketing.catalog_per_page`, default 9) dan tombol **Tampilkan lebih banyak tema** yang mengambil halaman berikutnya sebagai fragmen Blade yang sama (`?page=N&bagian=kartu`) — tanpa JavaScript, fragmen itu tetap tautan `href` biasa. Nilainya dirender dari `TemplateCatalogController` + `resources/views/templates/partials/cards.blade.php`, dengan JSON-LD `CollectionPage` + `ItemList` (`numberOfItems` = total hasil filter).
- Katalog berisi tiga belas template bertingkat: **Essential** (`classic-ivory`, `garden-botanical`, `modern-editorial`, `pesisir-senja`, `glasshouse-minimal`), **Signature** (`sage-keepsake`, `songket-empress`, `batik-pusaka`, `terracotta-sunset`), dan **Premium** (`emerald-gilded`, `midnight-celestial`, `velvet-royal`, `deco-noir`). Enam tema pertama mengikuti naskah cetak di `wedding-platform-blueprint/`; empat tema paling baru (`pesisir-senja`, `songket-empress`, `velvet-royal`, `deco-noir`) adalah renderer orisinal yang ditulis untuk katalog bertingkat ini:
  - **Emerald Gilded** — latar hijau zamrud, kartu krem bertepi ganda emas (oktagon `clip-path` + SVG bergaris `vector-effect`), nama mempelai dalam huruf sambung, sampul **BUKA** yang terbuka tanpa JavaScript (checkbox + `<label>`, konten tetap terbaca bila CSS gagal dimuat), dan **kode QR** menuju tautan tamu (atau URL kanonik halaman) yang dirender sebagai SVG inline oleh `app/Support/Invitations/QrCode.php`.
  - **Sage Keepsake** — spanduk sage vertikal “Mohon Doa Restu”, kertas krem miring dua kolom, dan ilustrasi bunga cat air SVG yang ditulis tangan. Di layar sempit kolom menumpuk; miringnya kertas dihapus saat `prefers-reduced-motion`.
  - **Batik Pusaka** — marun dan emas dengan pita parang dari gradien CSS (tanpa tekstur biner) serta gunungan wayang sebagai ornamen sampul dan penutup.
  - **Midnight Celestial** — langit biru malam dengan medan bintang bergradien, bulan sabit SVG, dan aksen champagne.
  - **Terracotta Sunset** — lengkung tanah liat di atas langit senja jingga dengan matahari yang terbenam di garis horizon.
  - **Glasshouse Minimal** — ruang putih lapang, jendela konservatori bergaris tipis, label sans-serif berjarak lebar, dan bagian bernomor 01/02/03.
  - **Pesisir Senja** — lengkung busur dan ombak dengan cahaya senja pesisir (`pesisir-senja.v1`).
  - **Songket Empress** — kain songket krem dengan anyaman emas dan motif pucuk rebisu (`songket-empress.v1`).
  - **Velvet Royal** — beludru plum, bingkai emas ganda, medalion monogram (`velvet-royal.v1`).
  - **Deco Noir** — art deco hitam dan champagne, sunburst geometris, huruf berjarak (`deco-noir.v1`).
- Template bertambah dengan **renderer Blade baru**, bukan dengan data: satu desain = view di `resources/views/templates/renderers/` + entri `app/Support/Templates/TemplateRegistry.php` + baris `database/seeders/TemplateSeeder.php` (kategori, `tier`, badge, `sort_order`) + palet kartu di `app/Support/Templates/TemplatePresentation.php` (kelas ini didaftarkan lewat `@source` di `resources/css/app.css` agar paletnya ikut ter-build). Wajah kartu katalog digambar dari palet milik renderer aslinya, jadi tidak ada thumbnail yang perlu diunggah.
- Tema Signature/Premium **ditegakkan sistem** (`app/Support/Billing/TemplateAccess.php`, lihat `adr/014-theme-catalogue-and-package-tiers.md`): plafon dibaca dari `service_entitlements.limits_json.features` yang dibekukan saat pesanan, ditolak saat **perpindahan** tema di penyimpanan draf, dan ditolak saat **penerbitan**. Draf yang sudah memakai tema itu tetap bisa disimpan, tema terkunci tetap tampil di pemilih sebagai opsi non-aktif, dan grant manual tanpa fitur beku dibaca sebagai plafon tertinggi.
- `GET /undangan/contoh/{slug-template}` merender template terbit dengan data fiktif (di `app/Support/Invitations/SampleInvitation.php`), boleh diindeks, dan memuat ajakan membuat undangan. Template tanpa versi terbit atau non-aktif menghasilkan 404.
- Blok tamu yang sama tersedia di semua template lewat `resources/views/invitations/partials/power.blade.php`: hitung mundur (**dihitung di server, jadi tetap benar tanpa JavaScript**), tombol **Simpan tanggal** (`GET /u/{slug}/kalender.ics`, dibangun dari snapshot terbit oleh `app/Support/Invitations/CalendarFile.php`, RFC 5545) dan **Google Calendar**, rekening hadiah yang **tidak langsung tampil** — tamu menekan tombol **Buka rekening hadiah** lebih dulu (native `<details>`, jadi tetap bisa dibuka tanpa JavaScript; labelnya berganti menjadi **Sembunyikan rekening** saat terbuka) lalu dapat menyalin dengan tombol **Salin nomor**, dinding ucapan, serta navigasi bagian yang melayang. Satu berkas `public/js/invitation.js` hanya memperbarui angka hitung mundur dan menyalin rekening; tanpa berkas itu semua isi tetap terbaca dan tautan kalender tetap berfungsi.
- Dinding ucapan hanya menampilkan pesan dari tamu yang mencentang “Tampilkan ucapan saya” saat mengisi RSVP (`rsvps.show_message`, bawaan mati untuk baris lama). Status kehadiran, jumlah rombongan, dan kontak tidak pernah ikut tampil; halaman contoh dan portofolio memakai daftar fiktif di `app/Support/Invitations/WishFeed.php` agar kata-kata tamu sungguhan tidak bocor ke portofolio.
- Encoder QR ditulis sendiri (byte mode, koreksi galat M, versi 1–10) tanpa dependensi Composer, sehingga tidak ada permintaan gambar keluar dari peramban tamu dan CSP `img-src` tetap ketat. Keluarannya diverifikasi dengan pembaca QR independen dan dikunci oleh matriks emas di `tests/Unit/QrCodeTest.php`.
- `GET /robots.txt` dan `GET /sitemap.xml` dilayani dari PHP (bukan file statis) agar host mengikuti `APP_URL`; keduanya hanya memuat halaman publik (`/`, `/tema`, `/inspirasi`, setiap halaman contoh tema, legal, masuk, daftar) dan menutup `/admin`, `/dashboard`, `/weddings`, `/orders`, `/checkout`, `/g/`, `/u/`, `/media/`.
- Halaman undangan nyata selalu `noindex,nofollow` karena memuat nama dan lokasi mempelai; hanya halaman portofolio yang diizinkan pemiliknya yang memakai canonical sendiri dan `index,follow`.

### Menyunting undangan (autosave)

- Editor (`/weddings/{id}/edit`) menyimpan draft otomatis 1,2 detik setelah Anda berhenti mengetik, lalu menampilkan status **Tersimpan HH:MM** di samping tombol **Simpan draft**. Tanpa JavaScript form tetap tersimpan lewat tombol biasa.
- Isian yang melanggar skema template dibalas sebagai 422 tanpa meninggalkan halaman; pesan pertama muncul sebagai “Belum tersimpan: …”. Perubahan yang belum tersimpan memicu peringatan saat halaman ditinggalkan.
- Autosave membuat revisi draft baru seperti simpan manual. Tautan publik tidak berubah sampai undangan diterbitkan ulang.

### Panel operator (`/admin`)

Akses hanya untuk akun ber-role `admin` (gate `access-admin`); pelanggan mendapat 403 dan tamu diarahkan ke login. Ketujuh layar memakai navigasi tab yang sama:

1. **Undangan** — daftar/detail semua undangan, pencarian judul/slug/email, dan penonaktifan publikasi (beralasan, tercatat di audit log). Operator tidak dapat mengubah draf atau data mempelai.
2. **Pengguna** — direktori akun dengan pencarian nama/email, filter peran (`customer`/`admin`), status (`active`/`suspended`), tag CRM, dan segmen (`Baru 30 hari`, `Sudah terbit`, `Tampil di inspirasi`, `Belum punya undangan`), plus jumlah undangan dan pesanan per akun. Detail akun memuat formulir CRM (catatan internal + maksimal 10 tag) dan ringkasan testimoni. Tombol **Unduh CSV** mengekspor hasil filter yang sama.
3. **Pesanan** — buku pesanan read-only dengan filter nomor/email pemilik/judul undangan dan status, plus ringkasan pendapatan: total dibayar, dibayar bulan berjalan, pesanan menunggu pembayaran, kode kedaluwarsa, dan rata-rata transaksi. Antrean **Perlu verifikasi** menandai pesanan yang bukti transfernya sudah diunggah; detail pesanan menampilkan rincian tagihan, riwayat konfirmasi, dan tombol **Verifikasi**/**Tolak** (alasan wajib) beserta tautan unduh bukti. Angka diambil dari total pesanan tersimpan; tidak ada harga yang dihitung ulang.
4. **Lead** — antrean formulir kontak dengan pencarian nama/email/WhatsApp, filter status, atribusi kampanye, dan tombol **Tandai sudah dihubungi**.
5. **Testimoni** — antrean moderasi; hanya testimoni `approved` yang tampil di landing. Menyetujui/menolak tercatat sebagai `testimonial.approved`/`testimonial.rejected`.
6. **Analitik** — funnel 30 hari terakhir (landing → lead → pendaftaran → terbit → undangan dibuka → RSVP) beserta perbandingan 30 hari sebelumnya dan konversi antar langkah.
7. **Audit log** — filter aksi, resource, aktor (nama/email), dan rentang tanggal inklusif (from/to), dengan metadata tiap catatan.

### CRM pelanggan, portofolio, testimoni, dan analitik

- **Tag dan catatan** hanya terlihat oleh operator dan disimpan di `users.crm_tags` (JSON) serta `users.crm_note`. Tag dinormalisasi menjadi huruf kecil, tanpa duplikat, maksimal 10 tag × 24 karakter. Perubahan dicatat sebagai `user.crm_updated` (isi catatan tidak disalin ke audit, hanya penanda ada/tidak).
- **Ekspor kampanye** (`GET /admin/users/export`) memakai filter yang sama dengan direktori dan menghasilkan CSV berisi nama, email, peran, status, tag, catatan, jumlah undangan/pesanan, dan tanggal daftar. Setiap sel melewati `CsvSanitizer` sehingga formula spreadsheet tidak dieksekusi, dan setiap ekspor dicatat sebagai `user.exported` beserta jumlah baris serta filter yang dipakai.
- **Portofolio (showcase)** diaktifkan pemilik dari halaman undangan setelah undangan terbit; menariknya bisa kapan saja. Halaman portofolio memakai snapshot terbit, menyertakan bilah penanda permanen, dan tidak memasang formulir RSVP maupun galeri. Setiap perubahan dicatat sebagai `wedding.showcase_enabled`/`wedding.showcase_disabled`.
- **Testimoni** ditulis pemilik undangan (1–5 bintang, 20–500 karakter) dan selalu masuk status `pending`. Menyunting testimoni yang sudah disetujui mengembalikannya ke antrean sehingga kata-kata baru tidak pernah tayang tanpa peninjauan.
- **Analitik funnel** mencatat enam event agregat: `landing_viewed`, `lead_captured`, `register_completed`, `invitation_published`, `invitation_viewed` (dengan `surface`: `public`, `personal`, `showcase`), dan `rsvp_submitted`. Tidak ada user id, IP, atau user agent di tabel `analytics_events`; kegagalan pencatatan dilaporkan ke log dan tidak menggagalkan halaman/RSVP.

### Menonaktifkan dan mengaktifkan akun

- Akun dinonaktifkan lewat **Pengguna → Detail → Nonaktifkan akun** dengan alasan wajib (4–200 karakter). Alasan dan aktor tercatat di audit log sebagai `user.suspended`.
- Status ini bukan penghapusan: undangan, pesanan, dan data tamu tetap utuh. Login berikutnya ditolak (`LoginRequest`) dan sesi yang masih berjalan berakhir pada permintaan berikutnya (`EnsureAccountIsActive`).
- Undangan yang sudah terbit tetap dapat dibuka tamu sampai publikasinya dinonaktifkan terpisah dari layar Undangan.
- Operator tidak dapat menonaktifkan akunnya sendiri maupun akun operator lain (403); aktivasi kembali dicatat sebagai `user.activated`.

### Verifikasi email dan notifikasi

- Akun baru wajib memverifikasi alamat email sebelum masuk ke area pelanggan (dashboard, undangan, pesanan, tamu, galeri, admin). Registrasi mengarah ke `GET /email/verify`, yang menampilkan alamat tujuan, tombol kirim ulang (`POST /email/verification-notification`, throttle 6/menit), dan tombol keluar.
- Tautan verifikasi bertanda tangan berlaku 60 menit dengan throttle 6/menit; tautan dengan hash akun lain atau tanpa tanda tangan ditolak (403).
- Notifikasi email yang terkirim: verifikasi alamat (`App\Notifications\VerifyEmailNotification`, menggantikan template bawaan Laravel), `OrderPaidNotification` setelah pembayaran terverifikasi (sekali per pesanan, setelah transaksi commit), `PaymentProofSubmitted` setiap pelanggan mengirim bukti transfer dari dashboard (tanpa email ini tidak ada sinyal bahwa dana mungkin sudah masuk), `AccountSuspendedNotification` saat operator menonaktifkan akun, `NewEnquiryReceived` untuk setiap formulir kontak, dan `RsvpReminderNotification` untuk tamu yang belum menjawab. Kegagalan pengiriman dilaporkan ke log, tidak menggagalkan pembayaran, suspend, unggah bukti, maupun penyimpanan lead.
- Semua email memakai satu kerangka: `resources/views/mail/layout.blade.php` (pita burgundy, monogram huruf sebagai pengganti `<img>` karena klien email memblokir gambar remote, footer berisi nama usaha + alamat + kontak) dan partial `paragraph`, `details`/`detail`, `button`, `quote`, `url`. Setiap template punya pasangan teks polos (`*-text.blade.php`); keduanya menerima data yang sama dan dikirim bersama sebagai alternatif HTML.
- Waktu di email ditampilkan dalam WIB (`Asia/Jakarta`) karena `APP_TIMEZONE=UTC`; nilai di database tetap UTC.
- Transport: `MAIL_MAILER=resend` dengan `RESEND_API_KEY` (paket `resend/resend-php`, sudah terpasang). `.env.example` sengaja memakai `log` agar klon baru tidak mengirim email; test memakai `array`.
- `MAIL_FROM_ADDRESS=onboarding@resend.dev` hanya boleh mengirim ke pemilik akun Resend. Selama domain belum terverifikasi, isi `MAIL_REDIRECT_TO` dengan kotak masuk pemilik akun: `App\Support\Mail\SandboxMailbox` memanggil `Mail::alwaysTo()` saat boot sehingga **semua** email — termasuk verifikasi dan pembayaran yang seharusnya ke pelanggan — mendarat di satu kotak itu dengan isi utuh, bukan gagal di provider. Kosongkan variabel itu pada hari `MAIL_FROM_ADDRESS` diganti ke domain terverifikasi; `docs/DEPLOYMENT.md` §2.1 mencatat urutannya.
- Jangan memakai `$message` sebagai nama data pada template email: Laravel menyuntikkan objek `Illuminate\Mail\Message` ke kunci itu tepat sebelum render dan nilainya akan tertimpa (pernah membuat kutipan enquiry gagal). `tests/Feature/MailTemplateTest.php` menguji jalur kirim sebenarnya lewat transport `array`, bukan hanya merender view.
- Notifikasi dikirim sinkron dalam request; belum ada antrean email terpisah, retry otomatis, atau pelacakan status terkirim/dibaca.

### Mengelola tamu dan RSVP

1. Buka detail undangan → **Tamu & RSVP** → **Kelola tamu**.
2. Tambahkan tamu satu per satu (nama, grup opsional, telepon/email opsional, ukuran rombongan). Jumlah tamu dibatasi `guest_limit` paket yang sedang aktif; tanpa paket aktif tidak ada batas.
3. Impor massal lewat CSV dengan header `name,group,phone,email,allowed_party_size` (sinonim `nama`/`nama_tamu`, `grup`/`kelompok`, `telepon`/`hp`/`no_hp`/`whatsapp`, `kuota`/`jumlah_tamu`/`pax` juga diterima). Halaman impor menyediakan **Unduh template CSV** berisi header dan dua contoh baris. Pemisah koma, titik koma (default Excel lokal Indonesia), dan tab dideteksi otomatis; BOM UTF-8 dan akhir baris CRLF aman. Satu baris tidak valid membatalkan seluruh berkas dan pesan menyebutkan nomor barisnya.
4. Ekspor CSV memuat status RSVP dan status tautan (bukan token); sel yang berpotensi menjadi formula Excel diberi awalan `'`.
5. **Buat tautan** menampilkan tautan personal sekali saja (`/g/{token}`) dan mencabut tautan sebelumnya. Tautan dapat dicabut manual; tamu yang membuka tautan yang dicabut atau kedaluwarsa mendapat 404.

Tamu membuka tautan personal untuk melihat undangan atas namanya dan mengisi RSVP (hadir/tidak/mungkin, jumlah rombongan, pesan). Respons terbaru menggantikan yang sebelumnya, dan jumlah rombongan tidak dapat melebihi ukuran yang ditetapkan pemilik undangan. Tautan belum dikirim otomatis; salin dan bagikan manual.

Pengingat RSVP tersedia lewat tombol **Kirim pengingat** (muncul bila undangan sudah terbit) atau scheduler:

```powershell
php artisan rsvp:remind                       # semua undangan terbit
php artisan rsvp:remind --dry-run             # hitung tanpa mengirim
php artisan rsvp:remind --wedding={slug}      # satu undangan
php artisan rsvp:remind --force               # abaikan jeda pengingat
```

Pengingat hanya menyasar tamu yang punya email, belum menjawab, dan tidak diingatkan dalam 7 hari terakhir (`GUEST_REMINDER_COOLDOWN_DAYS`, `GUEST_REMINDER_BATCH_LIMIT`). Setiap pengingat menerbitkan tautan personal baru dan mencabut tautan lama, karena token hanya disimpan sebagai hash; emailnya menyebutkan bahwa tautan tersebut menggantikan tautan sebelumnya. Pengiriman yang gagal tidak menandai tamu sebagai sudah diingatkan sehingga dicoba lagi, dan setiap proses tercatat sebagai `guest.reminders_sent`. Jadwal harian 09:00 terdaftar di `routes/console.php`.

Halaman personal memakai template yang sedang terbit (bukan halaman terpisah), menampilkan nama tamu di bilah atas, tautan galeri bila ada album publik berisi foto `ready`, serta meta Open Graph. Halaman publik tanpa token menampilkan template yang sama tanpa formulir RSVP dan tanpa nama tamu.

### Mengelola galeri, arsip, dan retensi

1. Buka detail undangan → **Galeri** → **Kelola galeri**.
2. Buat album (judul unik per undangan). Album `public` akan tampil di `http://localhost:8000/u/{slug}/galeri`; album `private` hanya terlihat oleh pemilik. Foto tanpa album tidak pernah tampil publik.
3. Unggah hingga 10 foto sekaligus (JPEG/PNG/WebP, maksimal 8 MB dan 24 MP per foto). Jumlah foto dibatasi `gallery_photos` dari paket aktif.

   Batas yang ditampilkan halaman galeri adalah nilai terendah antara config (`MEDIA_MAX_FILE_KB`, `MEDIA_MAX_UPLOAD_KB`) dan `upload_max_filesize`/`post_max_size` milik PHP, karena badan request yang terlalu besar dihentikan server sebelum aplikasi berjalan. Bila batas server lebih kecil, halaman memberi catatan; browser juga menolak kombinasi foto yang melebihi batas sebelum dikirim. Permintaan yang tetap terlalu besar dijawab halaman **413 — Unggahan terlalu besar** yang menyebut batas berlaku dan dicatat sebagai `request.post_too_large` di log. Default PHP (2 MB per file, `post_max_size` 8 MB) terlalu kecil untuk galeri; naikkan ke `upload_max_filesize=16M`, `post_max_size=48M`, dan `memory_limit=256M` (lihat `DEPLOYMENT.md` bagian 2.2 dan `MEDIA_STORAGE.md` bagian 10).
4. Unduhan memakai signed URL berumur 15 menit; tidak ada URL media yang permanen atau publik.
5. **Buat arsip** menghasilkan ZIP dari seluruh foto `ready` milik undangan tersebut (maksimal 200 foto/512 MB) dan berlaku 7 hari. Arsip `failed` dapat diulang; arsip `expired` tidak dapat diunduh dan dibersihkan otomatis.

Retensi berjalan otomatis lewat scheduler:

```powershell
php artisan media:cleanup          # hapus arsip kedaluwarsa + karantina hapus
php artisan media:reconcile        # laporkan file hilang/objek yatim
php artisan media:reconcile --strict   # exit code 1 bila ada drift (untuk alerting)
```

Jadwal `media:cleanup` setiap jam dan `media:reconcile` harian 02:30 terdaftar di `web/routes/console.php`; pastikan cron/scheduler host aktif. Media ditulis ke `storage/app/media` (privat), jadi direktori itu harus ikut di-backup terpisah dari database — lihat `BACKUP_RESTORE_DRILL.md`.

### Bagikan undangan dan ringkasan RSVP

Setelah undangan diterbitkan, halaman detail menampilkan panel **Bagikan undangan**: teks siap kirim berisi nama pasangan, tanggal acara pertama, lokasi, dan tautan publik; tombol **Bagikan via WhatsApp** dengan teks sudah terisi; **Salin teks undangan**; **Salin tautan**; serta tombol share bawaan perangkat bila didukung browser. Tautan personal untuk tamu dibuat terpisah di Kelola tamu. Aplikasi tidak melacak status terkirim/dibaca.

Kartu **Tamu & RSVP** menampilkan jumlah diundang, respons, hadir beserta jumlah orang, tidak hadir, masih ragu, belum merespons, dan tingkat respons. Angka dihitung dari satu query terkelompok pada data terkini; tidak ada riwayat atau tren.

### Keamanan akun dan hardening produksi

- **Autentikasi dua faktor (TOTP)** ada di `/pengaturan/keamanan` (tautan **Keamanan** di header). Halaman menampilkan kunci manual dan URI `otpauth://` (tanpa kode QR), konfirmasi satu kode 6 digit, lalu 8 kode pemulihan sekali pakai. Secret TOTP disimpan terenkripsi dan kode pemulihan disimpan sebagai hash SHA-256. Akun ber-2FA tidak mendapat sesi setelah kata sandi saja; verifikasi dibatasi 5 percobaan/menit. Set `ADMIN_REQUIRE_TWO_FACTOR=true` di produksi agar operator yang belum mendaftar dialihkan dari panel admin.
- **CSP report-only**: setiap respons membawa `Content-Security-Policy-Report-Only` + `Reporting-Endpoints`. Browser mengirim pelanggaran ke `POST /csp-reports`, yang mencatatnya ke log (dipotong, tanpa query string). Set `SECURITY_CSP_REPORT_ONLY=false` setelah laporan ditinjau; template masih memakai gaya inline sehingga `style-src` memuat `'unsafe-inline'`.
- **Cookie sesi**: `.env.example` memakai `SESSION_ENCRYPT=true`, dan cookie `Secure` otomatis aktif saat `APP_ENV=production`.
- **Pemindai unggahan**: `MEDIA_SCAN_COMMAND` (mis. `clamdscan --no-summary --stdout`) dijalankan sebelum berkas disimpan. Keluaran `FOUND` atau pemindai yang gagal menolak unggahan selama `MEDIA_SCAN_FAIL_CLOSED=true`.
- **Cache media**: thumbnail/preview memakai signed URL objek yang tidak pernah ditulis ulang dan disajikan `private, max-age=31536000, immutable`, dengan `srcset` 400w/1280w di galeri.

## Test dan kualitas

```powershell
cd web
php artisan test              # 228 test: 227 lulus, 1 skip khusus CI
php vendor\bin\pint --quiet   # formatter
npm run build                 # Vite + Tailwind
$env:COMPOSER_HOME='d:\project-code\www\undangan\.local\composer'; composer audit
```

### CI

`.github/workflows/ci.yml` menjalankan tiga job:

| Job | Isi |
| --- | --- |
| quality | `composer validate`, `pint --test`, `composer audit`, `npm ci` + `npm run build` |
| tests-sqlite | seluruh suite pada SQLite `:memory:` |
| tests-postgres | `migrate --force` dan seluruh suite pada service `postgres:17-alpine` |

Job PostgreSQL menset `EXPECTED_DB_DRIVER=pgsql`; `DatabaseDriverTest` gagal bila suite
ternyata berjalan pada driver lain, sehingga klaim parity tidak bisa lolos diam-diam.
Repositori lokal sudah diinisialisasi pada cabang `main` dengan satu commit baseline, tetapi
workflow belum pernah dieksekusi karena belum ada remote dan Docker tidak tersedia di mesin ini.

## Catatan Composer di mesin ini

`repo.packagist.org` diblokir pada jaringan mesin pengembangan ini, sehingga mirror Aliyun dipakai lebih dulu (metadata kedaluwarsa) lalu diganti jembatan metadata lokal. Untuk instalasi ulang di mesin ini:

```powershell
# Terminal 1
php -d "curl.cainfo=C:/Program Files/Git/usr/ssl/certs/ca-bundle.crt" -S 127.0.0.1:8787 tools/packagist-proxy.php

# Terminal 2
$env:COMPOSER_HOME="$PWD\.local\composer"
cd web; composer install
```

`web/composer.json` tetap memakai Packagist resmi; proxy hanya konfigurasi mesin (`.local/`, diabaikan git). Di jaringan normal, `composer install` biasa sudah cukup.

## Database

- Lokal/test: SQLite (`DB_CONNECTION=sqlite`, test memakai `:memory:`).
- Produksi: PostgreSQL 17. `docker compose up -d postgres`, lalu salin `web/.env.postgres.example` ke `.env` dan sesuaikan kredensial. Parity PostgreSQL dijalankan otomatis oleh job CI `tests-postgres`; di mesin ini belum dieksekusi (tanpa Docker/`pdo_pgsql`).

## Dokumentasi

- `wedding-platform-blueprint/docs/AUDIT_REPORT.md` — audit repo, lingkungan, konflik, risiko.
- `wedding-platform-blueprint/docs/PHASE_1_PLAN.md` — ruang lingkup fase 1, acceptance criteria, hasil test.
- `wedding-platform-blueprint/docs/PHASE_2_3_REPORT.md` — template, admin, order/pembayaran, hasil test.
- `wedding-platform-blueprint/docs/PHASE_4_REPORT.md` — tamu, tautan personal, RSVP, hasil test.
- `wedding-platform-blueprint/docs/PHASE_5_7_REPORT.md` — galeri/arsip, hardening beta, share link/analitik, hasil test.
- `wedding-platform-blueprint/docs/BACKUP_RESTORE_DRILL.md` — drill restore dan prosedur produksi.
- `wedding-platform-blueprint/docs/SUPPORT_PLAYBOOK.md` — runbook support, insiden, dan QA manual.
- `wedding-platform-blueprint/docs/adr/004-laravel-13.md`
- `wedding-platform-blueprint/docs/adr/005-sqlite-local-postgres-production.md`
- `wedding-platform-blueprint/docs/adr/006-public-link-and-timezone.md`
- `wedding-platform-blueprint/docs/adr/007-orders-and-payments.md`
- `wedding-platform-blueprint/docs/adr/008-guests-and-rsvp.md`
- `wedding-platform-blueprint/docs/adr/009-media-storage-and-archive.md`
- `wedding-platform-blueprint/docs/adr/010-beta-hardening-and-growth-scope.md`
- `wedding-platform-blueprint/docs/adr/011-manual-bank-transfer-payments.md`
- `wedding-platform-blueprint/docs/adr/012-mobile-first-ui.md`
- `wedding-platform-blueprint/docs/adr/013-brand-identity-and-icon-set.md`
- `wedding-platform-blueprint/docs/adr/014-theme-catalogue-and-package-tiers.md`
- `wedding-platform-blueprint/docs/adr/015-landing-services-and-honest-numbers.md`
- `wedding-platform-blueprint/docs/adr/016-landing-overhaul-and-inviee-benchmarking.md`

## Batasan yang diketahui

- Pembayaran memakai transfer bank manual: verifikasi bukti dilakukan operator, belum ada rekonsiliasi otomatis dengan mutasi bank, refund, atau chargeback. Gateway `fake` hanya untuk demo/test dan harus mati di produksi (`PAYMENT_FAKE_ENABLED=false`, `PAYMENT_GATEWAY=bank_transfer`).
- Guest management ada; pengingat RSVP dikirim lewat email ke tamu yang memberi alamat, tetapi tautan personal awal masih dibagikan manual (WhatsApp/email), token tidak punya masa berlaku bawaan, dan guestbook/ucapan belum ada.
- Media memakai disk lokal privat, bukan object storage; adapter S3 belum terpasang (`league/flysystem-aws-s3-v3`), sehingga upload dan pembuatan arsip berjalan sinkron di dalam request.
- Build PHP ini hanya memiliki encoder PNG di GD; preview/thumbnail tidak terbentuk dan galeri menyajikan original lewat signed URL sampai GD dengan JPEG/WebP tersedia. Pemindai malware bersifat opsional lewat `MEDIA_SCAN_COMMAND`; tanpa itu hanya ada validasi MIME/dimensi.
- Kuota media tidak memakai reservasi atomik; unggahan paralel dapat melewati batas sebanyak satu batch. Batas ukuran efektif mengikuti PHP host (`App\Support\Media\UploadLimits`): dengan php.ini bawaan hanya 2 MB per foto dan ±7,5 MB per kirim, sehingga foto ponsel berukuran besar harus dipecah atau host dinaikkan batasnya.
- Panel admin mencakup daftar/detail undangan, direktori pengguna dengan suspend/aktivasi dan CRM (catatan/tag/segmen/ekspor CSV), buku pesanan dengan verifikasi/tolak bukti transfer, antrean lead, moderasi testimoni, funnel analitik agregat, dan audit log berfilter; belum ada rekonsiliasi pembayaran otomatis maupun pengiriman kampanye email langsung dari panel.
- Share link tidak melacak status terkirim/dibaca; analitik RSVP hanya potret saat ini (tanpa riwayat/tren). Analitik funnel bersifat agregat 30 hari tanpa atribusi per pengguna; tidak ada integrasi Google Analytics/pixel, kontribusi foto tamu, WhatsApp Business, custom domain, portal mitra, referral, dan multi-bahasa sampai ada bukti pelanggan. Tema baru tetap bertambah lewat renderer Blade, bukan lewat panel.
- Bagian layanan di beranda adalah daftar kerja mitra dari `config('site.services')`: belum ada kontrak, katalog harga, kalender ketersediaan, maupun pemesanan di dalam aplikasi. Yang terjadi hanyalah lead yang tersimpan dan dikirim ke email usaha, jadi setiap pertanyaan layanan menuntut jawaban manual (lihat `adr/015-landing-services-and-honest-numbers.md`).
- CSP berjalan report-only dan belum ditegakkan (template masih memakai gaya inline); belum ada error tracking eksternal (Sentry), uptime alerting, maupun drill restore PostgreSQL di staging; QA mobile/browser dan pilot pelanggan belum dijalankan.
- Semua test berjalan pada SQLite. Parity PostgreSQL hanya dijalankan oleh job CI `tests-postgres`, yang belum pernah dieksekusi karena belum ada remote.
- Email transaksional mencakup verifikasi alamat, konfirmasi pembayaran, pemberitahuan suspend, dan pengingat RSVP (sinkron, tanpa antrean/retry otomatis dan tanpa pelacakan terkirim/dibaca); belum ada pengingat masa aktif.
- Slug tidak dapat diubah setelah dibuat; belum ada redirect.
- Email reset dan notifikasi: `.env.example` memakai driver `log` (isi email masuk ke `storage/logs/laravel.log`), sedangkan `.env` pengembangan memakai `resend` dengan kunci nyata sehingga email benar-benar terkirim.
- Ikon, manifest, dan OG image memakai warna merek (burgundy `#A6294B`, ivory `#FFF9F4`), sedangkan antarmuka masih memakai token Tailwind `rose-*`/`stone-*`; penyelarasan palet belum dikerjakan.
