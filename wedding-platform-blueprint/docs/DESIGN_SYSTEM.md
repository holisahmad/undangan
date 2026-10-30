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

## 2. Starter palette

Nilai awal untuk diuji:
- Dusty Rose: `#C9828C`
- Sage Green: `#A9B8A0`
- Warm Ivory: `#FAF7F2`
- White: `#FFFFFF`
- Champagne: `#D7BC91`
- Deep Charcoal: `#353238`
- Warm Gray: `#77716F`
- Success: `#537A60`
- Error: `#B95353`

Champagne tidak boleh menjadi warna teks kecil di atas background terang jika kontras tidak memenuhi standar.

## 3. Typography

Kandidat:
- Cormorant Garamond untuk heading dekoratif.
- Inter untuk body, form, tabel, dan dashboard.

Periksa lisensi font, fallback, ukuran file, dan loading behavior. Jangan mengunduh font berlebihan.

## 4. Layout

- Mobile-first.
- Gunakan spacing scale konsisten.
- Dashboard mengutamakan keterbacaan dan efisiensi.
- Halaman undangan dapat lebih emosional, tetapi konten acara harus mudah ditemukan.
- Formulir memiliki label permanen, hint yang berguna, dan error yang dekat dengan field.
- Tombol utama memiliki state default, hover, focus, loading, disabled, dan success/error bila relevan.

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
