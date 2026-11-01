# Design System

## 1. Brand attributes

- Elegant
- Romantic
- Soft
- Cheerful
- Calm
- Modern
- Trustworthy

Jangan menerjemahkan atribut tersebut menjadi dekorasi berlebihan. Ruang kosong, tipografi, hierarki, dan foto berkualitas lebih penting daripada banyak ornamen.

### 1.1 Identitas merek yang dipakai

- Nama: **Wedly** (`APP_NAME`), dari *wedding* + akhiran *-ly*.
- Nama usaha: **Wedly Studio** (`config('site.operator')`). Produk disebut Wedly di header dan judul tab; usaha menyebut dirinya Wedly Studio di blok kontak, halaman legal, `<footer>` (`© tahun Wedly Studio`), dan `legalName` JSON-LD. Jangan menukar keduanya.
- Tagline: *Invitation · Celebration · Forever* (`config('site.tagline')`).
- Kredit pembangun: "Dibangun oleh Numos Studio" (`config('site.credit')`), tampil di footer halaman pemasaran.
- Warna merek untuk aset gambar: Burgundy `#A6294B`, Rose Pink `#E87983`, Champagne Gold `#D99A55`, Warm Ivory `#FFF9F4`.
- Antarmuka tetap memakai token Tailwind `rose-800`/`rose-900` dan `stone-50`/`stone-600`; penyelarasan ke nilai hex merek adalah pekerjaan terpisah (lihat `adr/013-brand-identity-and-icon-set.md`).
- Emblem ditampilkan berdampingan dengan nama merek pada header pemasaran (32 px), header aplikasi (28 px), halaman tamu (48 px), dan blok merek di footer (40 px). Gambar memakai `alt=""` karena nama merek sudah tertulis sebagai teks di sebelahnya.

### 1.2 Ikon, manifest, dan gambar berbagi

- Seluruh `<link rel="icon">`, `apple-touch-icon`, `site.webmanifest`, `theme-color`, dan nama aplikasi dimuat dari satu partial: `resources/views/partials/brand-head.blade.php`. Jangan menambah `rel="icon"` langsung di layout.
- Halaman undangan memakai partial yang sama (`invitations/partials/head-meta.blade.php`) supaya pintasan layar utama tamu memakai ikon merek.
- Halaman error 413 memuat ikonnya sendiri karena dirender tanpa layout.
- Ikon diregenerasi dengan `php tools/brand/build-icons.php` lalu di-commit; jangan menyunting berkas PNG secara manual karena ukuran dan padding akan menyimpang.
- Gambar berbagi `og-wedly.png` berukuran 1200x630 dengan latar Warm Ivory; setiap halaman pemasaran mendeklarasikan `og:image` beserta lebar dan tingginya.

### 1.3 Blok kontak usaha

- Satu partial, `resources/views/partials/business-contact.blade.php`, merender nama usaha, email, telepon (`tel:`), WhatsApp, alamat pos, dan tautan Google Maps. Halaman privasi dan syarat memakainya; footer pemasaran dan kartu kontak beranda memakai data yang sama dari `config/site.php`.
- Nomor telepon diterima dalam bentuk lokal (`0813…`) dan selalu diubah oleh `App\Support\Contact\PhoneNumber` sebelum menjadi tautan: `tel:+62…` dan `https://wa.me/62…`. `wa.me/0813…` ditolak WhatsApp, jadi bentuk lokal tidak boleh dirangkai manual di view.
- Halaman masuk dan dashboard juga memuat tautan bantuan, karena pelanggan yang tertahan di tengah pesanan tidak selalu kembali ke landing page.

### 1.4 Email transaksional

- Kepala email memakai pita burgundy `#A6294B` dengan monogram huruf di dalam lingkaran ivory, bukan `<img>`: klien email memblokir gambar remote secara default, sehingga emblem yang gagal dimuat tampil sebagai kotak rusak pada sebagian penerima. Nama produk (Georgia) di kiri, nama usaha (Helvetica, kapital berjarak) di kanan.
- Isi mengikuti pola label/nilai: label 10 px kapital berjarak 2 px warna `#A79C8E`, nilai 15 px `#1C1917`, garis pemisah `#EFE6DA`; satu tombol utama burgundy dan satu tombol sekunder bergaris hanya bila memang ada aksi alternatif (balas via WhatsApp).
- Kaki email memuat nama usaha, alamat pos dari `config/site.php`, email, telepon, dan host situs, ditutup satu kalimat yang menjelaskan mengapa email itu dikirim.
- Semua gaya ditulis inline dan disusun dengan tabel; flexbox maupun `<style>` di `<head>` tidak dijamin sampai ke Gmail dan Outlook. Setiap template punya versi teks polos dengan data yang sama.
- Gaya bersyarat memakai direktif `@style([...])`, bukan `{{ }}` di dalam atribut `style`: direktif itu merangkai seluruh atribut (dipisah `; `) sehingga nilai kondisional seperti garis bawah baris terakhir atau warna tombol sekunder tidak menyisakan atribut setengah jadi. Interpolasi Blade di dalam atribut juga dibaca sebagai CSS oleh language service editor dan memunculkan diagnostik palsu; komentar template tidak boleh memuat tag mentah seperti `<style>` karena parser lalu memperlakukan blok di bawahnya sebagai CSS.
- Waktu ditulis dalam WIB dengan label eksplisit (`Asia/Jakarta`) karena penyimpanan UTC; URL panjang di badan email dipisah ke blok sendiri dengan `word-break:break-all` agar tidak melebar di layar ponsel.

## 2. Palette

Palet merek — dipakai apa adanya di aset gambar (ikon, manifest, `og:image`):

- Burgundy: `#A6294B` — warna utama; menjadi `theme_color` manifest dan halaman.
- Rose Pink: `#E87983` — aksen hangat.
- Champagne Gold: `#D99A55` — aksen premium.
- Warm Ivory: `#FFF9F4` — latar aset dan tile ikon.
- White: `#FFFFFF`.

Pemetaan ke token antarmuka (Tailwind, sudah dipakai seluruh aplikasi):

- Aksi utama: `rose-800` (`#9F1239`), hover `rose-900` (`#881337`).
- Latar halaman: `stone-50` (`#FAFAF9`); kartu dan header: putih.
- Teks: `stone-900` (`#1C1917`), sekunder `stone-600` (`#57534E`), garis `stone-200` (`#E7E5E4`).
- Status: `emerald-50`/`emerald-900` (sukses), `rose-50`/`rose-900` (error), `amber-50`/`amber-900` (peringatan).
- Sorotan harga dan CTA akhir memakai `bg-rose-900` dengan teks putih.

Burgundy `#A6294B` dan `rose-800` tidak identik: jangan mencampur keduanya dalam satu komponen. Penyelarasan penuh (mengganti setiap `rose-800` menjadi `#A6294B`) belum dikerjakan dan harus diuji kontrasnya lebih dulu — lihat `adr/013-brand-identity-and-icon-set.md`.

Champagne tidak boleh menjadi warna teks kecil di atas background terang jika kontras tidak memenuhi standar. Palet hipotesis awal — Dusty Rose `#C9828C`, Sage Green `#A9B8A0`, Champagne `#D7BC91`, Warm Ivory `#FAF7F2` — digantikan oleh palet di atas dan tidak dipakai lagi.

## 3. Typography

Implementasi aktif (per November 2026, ADR 016):
- **Heading & Display**: Memakai Google Font `Playfair Display` (diimpor via preconnect di `resources/views/partials/assets.blade.php`), dipetakan ke `--font-serif` di Tailwind CSS 4 dengan fallback Georgia dan Iowan Old Style. Memberikan kesan romantis, berkelas, dan mewah.
- **Body, Form & Dashboard**: Memakai Google Font `Plus Jakarta Sans` / `DM Sans`, dipetakan ke `--font-sans` di Tailwind CSS 4. Memberikan legibilitas tinggi, modern, bersih, dan nyaman dibaca di layar smartphone.
- **Undangan Kustom**: Judul undangan pada template individual tetap memakai `clamp()` untuk responsivitas viewport mobile-first.

Skala: `text-xs` hingga `text-6xl`; heading memakai `font-bold` dan `font-semibold` dengan tracking seimbang.

## 4. Layout

- Mobile-first.
- Gunakan spacing scale konsisten.
- Dashboard mengutamakan keterbacaan dan efisiensi.
- Halaman undangan dapat lebih emosional, tetapi konten acara harus mudah ditemukan.
- Formulir memiliki label permanen, hint yang berguna, dan error yang dekat dengan field.
- Tombol utama memiliki state default, hover, focus, loading, disabled, dan success/error bila relevan.

### 4.1 Aturan mobile-first yang dipakai implementasi

- Kelas tanpa prefix ditulis untuk layar sempit; `sm:`/`md:`/`lg:` hanya menambah ruang di layar besar. Jangan mulai dari `hidden lg:flex` lalu menambal mobile.
- Navigasi atas memakai menu `<details>` (tanpa JavaScript) di bawah breakpoint `lg` untuk area akun dan `sm` untuk halaman pemasaran. Target sentuh minimal 40x40 px (`h-10 w-10`) dan item menu `py-2.5`.
- Tabel lebar selalu dibungkus `overflow-x-auto` agar halaman tidak pernah menggulir horizontal. Daftar pesanan pelanggan memakai kartu di bawah `sm` dan tabel di atasnya.
- Filter pencarian memakai `w-full` di mobile dan `sm:min-w-*` hanya di layar lebar.
- Tombol aksi utama memakai `py-3` di mobile dan `sm:py-2` di desktop agar tinggi sentuh ±44 px.
- Input, select, dan textarea mendapat font 16 px di layar ≤640 px supaya iOS tidak memperbesar halaman saat fokus (`resources/css/app.css`).
- `html { scroll-padding-top: 5rem }` menjaga anchor (`#harga`, `#faq`) tidak tertutup header sticky.
- Nomor pesanan, nomor rekening, dan email memakai `break-words`/`overflow-wrap` sehingga tidak melebarkan halaman.
- Template undangan memakai satu kolom dengan `clamp()` untuk judul dan padding lebih rapat di bawah 480 px. Satu-satunya pengecualian adalah **Sage Keepsake**, yang memakai dua kolom hanya mulai 720 px; di bawahnya kolom menumpuk.

## 5. Komponen inti

- Button
- Text input
- Textarea
- Select/combobox
- Date/time input
- File uploader
- Image crop/preview
- Dialog/modal
- Toast/alert
- Tabs
- Card
- Table/list
- Pagination
- Badge/status
- Empty state
- Loading skeleton
- Confirm destructive action
- Publish status panel
- Expiry warning
- Template card
- Invitation preview frame

Gunakan komponen bersama untuk konsistensi, tetapi jangan memaksakan semua UI ke komponen generik yang terlalu kompleks.

## 6. Invitation template rules

- Template harus mobile-first dan diuji pada viewport sempit.
- Informasi inti tidak boleh terkubur di bawah animasi.
- Hindari autoplay audio/video yang mengejutkan pengguna.
- Hormati `prefers-reduced-motion`.
- Sediakan fallback jika media gagal dimuat.
- Tanggal, waktu, dan lokasi harus jelas.
- Kontras teks memenuhi target aksesibilitas.
- Foto memiliki ukuran dan crop yang dapat diprediksi.
- Template mempunyai daftar fitur yang didukung dan schema konfigurasi tervalidasi.

### 6.1 Katalog template terbit

Urutan tabel di bawah adalah urutan kurasi yang dibaca antarmuka (`invitation_templates.sort_order`), jadi halaman `/tema`, pemilih tema di dashboard, dan strip beranda menampilkan desain dalam urutan yang sama.

| Slug | Renderer | Paket | Kategori | Palet | Ciri |
| --- | --- | --- | --- | --- | --- |
| `classic-ivory` | `classic-ivory.v1` | Essential | klasik | krem `#fbf8f1`, cokelat `#e4dbc9`, emas usang `#a98b5f` | serif klasik, bingkai tipis |
| `emerald-gilded` | `emerald-gilded.v1` | Premium | mewah | zamrud `#04231b`, emas `#c8a96a`, krem `#f3ecdc` | kartu oktagon bergaris ganda, sampul BUKA, kode QR |
| `garden-botanical` | `garden-botanical.v1` | Essential | botani | hijau daun `#b7c9a8`, `#5b7553` | bingkai kertas, ornamen pembatas |
| `midnight-celestial` | `midnight-celestial.v1` | Premium | mewah | malam `#080d1c`, `#1b2652`, champagne `#e3c9a0` | bulan sabit dan bintang, medalion |
| `modern-editorial` | `modern-editorial.v1` | Essential | modern | putih, hitam `#1c1917` | sans-serif, tata letak editorial |
| `pesisir-senja` | `pesisir-senja.v1` | Essential | pesisir | `#eef6f5`, `#9cc6c9`, jingga hangat `#e0a06a` | lengkung busur dan ombak |
| `sage-keepsake` | `sage-keepsake.v1` | Signature | botani | sage `#a9b79e`, teal `#34524a`, krem `#f8f3e8` | spanduk vertikal, kertas miring dua kolom, bunga cat air |
| `songket-empress` | `songket-empress.v1` | Signature | adat | krem `#fdf6e4`, emas `#d8b457`, `#a4761f` | anyaman songket, motif pucuk rebisu |
| `batik-pusaka` | `batik-pusaka.v1` | Signature | adat | marun `#3d1216`, emas `#c9a227`, `#e6c98a` | motif parang, gunungan wayang |
| `terracotta-sunset` | `terracotta-sunset.v1` | Signature | modern | tanah liat `#b4552f`, `#e79a5c`, krem `#f7ecdc` | lengkung senja jingga |
| `velvet-royal` | `velvet-royal.v1` | Premium | mewah | plum `#2c0f26`, `#5a1f47`, emas `#c9a24b` | bingkai emas ganda, medalion monogram |
| `deco-noir` | `deco-noir.v1` | Premium | mewah | hitam `#111114`, `#3a3630`, champagne `#cbb27a` | sunburst art deco, huruf berjarak |
| `glasshouse-minimal` | `glasshouse-minimal.v1` | Essential | minimalis | putih `#f7f6f1`, `#dfe4dc`, hijau kabut `#63776a` | ruang putih lapang, jendela konservatori |

- Kolom **Paket** adalah `invitation_templates.tier`. Desain Signature/Premium tetap ditampilkan penuh di katalog — dengan catatan paket pembukanya — tetapi tidak dapat dipilih atau diterbitkan sebelum paket itu aktif (ADR-014).
- Wajah kartu katalog (bingkai mini-undangan, pita warna, label kategori/paket, badge `Baru`/`Favorit`) diambil dari `App\Support\Templates\TemplatePresentation`, bukan dari kolom database dan bukan dari gambar unduhan. Renderer baru menambah satu entri di kelas itu; `resources/css/app.css` memuat `@source` ke kelas tersebut agar palet Tailwind-nya ikut ter-build.
- **Beranda hanya menampilkan strip terkurasi** (`platform.marketing.landing_template_limit`) dengan tautan "Lihat semua N tema" ke `/tema`. Ketika katalog tumbuh, yang bertambah adalah halaman katalog, bukan panjang beranda.
- **`/tema` adalah halaman katalog**: filter kategori + paket dengan penghitung per pilihan, paginasi server (`platform.marketing.catalog_per_page`), dan tombol "Tampilkan lebih banyak tema" yang mengambil halaman berikutnya sebagai fragmen HTML yang sama (`?page=N&bagian=kartu`). Tanpa JavaScript, fragmen itu tetap menjadi tautan `href` biasa.
- Setiap template adalah view Blade mandiri dengan blok `<style>` sendiri; tidak ada aset gambar biner, tidak ada webfont, dan tidak ada permintaan jaringan tambahan. Ornamen digambar sebagai SVG inline supaya CSP `img-src 'self' data:` dan `font-src 'self' data:` tetap ketat.
- Huruf sambung memakai tumpukan font sistem (`"Segoe Script"`, `"Snell Roundhand"`, `"Apple Chancery"`, `cursive`) — bukan webfont — dengan cadangan italic serif.
- **Sampul (cover gate)** hanya memakai checkbox tersembunyi dan `<label>`, jadi undangan terbuka tanpa JavaScript dan tetap bisa dibaca pembaca layar; isi undangan tetap ada di DOM meskipun sampul belum dibuka, dan `@media print` menyembunyikan sampul.
- **Kode QR** dirender dari `app/Support/Invitations/QrCode.php` (byte mode, koreksi galat M, versi 1–10, SVG inline tanpa `<image>`). Muatannya adalah tautan tamu bila halaman personal, selain itu URL kanonik halaman itu sendiri, sehingga kode yang dicetak tidak pernah menuju rute yang tidak ada.
- Bentuk kartu memakai `clip-path` poligon dengan chamfer kecil; jarak dalam kartu harus lebih besar daripada potongan sudut agar teks tidak terpotong.

## 7. UX states

Setiap fitur penting harus memiliki:
- Loading.
- Empty.
- Success.
- Error.
- Disabled.
- Unauthorized/expired bila relevan.

Autosave harus menunjukkan status, misalnya “Menyimpan…”, “Tersimpan”, atau “Gagal menyimpan — coba lagi”. Hindari menyatakan tersimpan sebelum server mengonfirmasi.

## 8. Accessibility

- Target WCAG 2.2 AA sejauh relevan.
- Kontras teks yang memadai.
- Keyboard focus terlihat.
- Form memiliki label.
- Jangan memakai warna sebagai satu-satunya penanda status.
- Gambar informatif memiliki alt text.
- Modal mengelola focus dengan benar.
- Ukuran target sentuh memadai untuk ponsel.

## 9. Performance

- Gunakan image sizing dan responsive images.
- Lazy-load media di bawah fold.
- Jangan preload semua foto galeri.
- Hindari animasi berat.
- Ukur Core Web Vitals di halaman publik.
- Hindari layout shift saat font/gambar dimuat.

## 10. Design QA

Sebelum merge:
- Periksa mobile dan desktop.
- Periksa konten panjang dan nama panjang.
- Periksa data kosong.
- Periksa error server.
- Periksa akses keyboard.
- Periksa kontras.
- Periksa hasil preview dibanding halaman publik.
