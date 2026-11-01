# Testing Strategy

## 1. Piramida pengujian

- Unit tests untuk business rules dan state transitions.
- Integration tests untuk database, storage adapter, queue, dan payment adapter.
- Feature/API tests untuk autentikasi, otorisasi, validasi, dan response.
- End-to-end tests untuk alur penting pelanggan.
- Security tests untuk tenant isolation, upload, webhook, dan token.
- Manual visual QA untuk template dan responsive layouts.

## 2. Prioritas test

### Identity/access
- Register/login/reset.
- Unauthorized access.
- User A tidak dapat mengakses wedding milik User B.
- Role admin terbatas.

### Invitation
- Draft save.
- Publish validation.
- Publish version snapshot.
- Unpublish/expire.
- Template version compatibility.
- Preview konsisten dengan publication.
- Seluruh renderer yang terdaftar diuji lewat rute publik yang sama: `data-template` yang benar, nama mempelai tampil, dan setiap `renderer_key` punya view (`TemplateCatalogTest`). Test membaca `TemplateRegistry`, tidak menyimpan jumlah template, jadi menambah tema tidak mengubah test.
- Sampul BUKA di `emerald-gilded` diuji sebagai markup: checkbox `id`/`label for` yang sama sehingga undangan bisa dibuka tanpa JavaScript, plus SVG QR dengan `aria-label` yang stabil.
- Encoder QR diuji sebagai unit (`QrCodeTest`): ukuran matriks untuk versi 1/2/10, quiet zone empat modul, pola pencari di tiga sudut, penolakan muatan kosong atau >213 byte, dan satu matriks emas (SHA-1) yang dulu dipastikan bisa dipindai pembaca QR independen. Perubahan pada Reed-Solomon, interleaving, atau pemilihan mask akan memecahkan matriks itu.
- Plafon tema per paket diuji pada kedua tegaknya (`ThemeCatalogTest`): perpindahan draf ke tema di atas plafon ditolak (422), draf yang sudah memakai tema itu tetap tersimpan, penerbitan tema Premium dengan plafon Essential ditolak, upgrade membuka tema tanpa menyentuh draf, dan grant manual tanpa `features` tidak mengunci pelanggan.
- Kontrak browsing katalog ikut dikunci, bukan jumlah kartunya: filter kategori/paket, nilai filter tak dikenal tidak menggagalkan halaman, kombinasi kosong menunjuk kembali ke katalog, paginasi + fragmen `?page=N&bagian=kartu` (halaman terakhir berhenti menawarkan "load more"), catatan paket pembuka pada kartu, dan entri `/tema` di sitemap (`ThemeCatalogTest`).

### Payment
- Valid webhook.
- Invalid signature.
- Duplicate event.
- Amount mismatch.
- Redirect palsu.
- Retry dan status pending.

### Guest/RSVP
- Token valid/revoked/expired.
- RSVP party size tidak melebihi batas.
- Rate limit.
- Data guest tidak bocor.

### Media/archive
- Upload valid/invalid.
- Quota race condition.
- File ownership.
- Signed URL expiry.
- ZIP isolation.
- Job retry.
- Retention and deletion.
- Storage reconciliation.

### UI
- Empty/loading/error/success states.
- Mobile layout.
- Long names/address.
- Reduced motion.
- Keyboard/focus.
- Contrast.
- Ikon, manifest, `theme-color`, dan `og:image` dideklarasikan di `<head>` dan berkasnya benar-benar ada (bukan 0 byte); favicon yang kosong tetap tampak seperti markup benar, jadi ukuran berkas ikut diuji (`FrontendAssetsTest`).
- Kontak usaha tampil di beranda dan halaman legal dengan tautan yang bisa diklik: `mailto:`, `tel:+62…`, `https://wa.me/62…`, dan `href` Google Maps, serta `telephone`/`PostalAddress`/`hasMap` di JSON-LD (`LandingAndDiscoveryTest`). Nomor lokal `0813…` tidak boleh lolos menjadi tautan mentah — perilaku normalisasinya diuji terpisah (`PhoneNumberTest`).
- Enquiry dari formulir `POST /kontak` benar-benar sampai ke email usaha, bukan hanya tersimpan di database (`MarketingGrowthTest`).
- Bagian layanan pelaminan hanya tampil bila `config('site.services')` berisi, menyebut mitranya dan kalimat bahwa harga dikonfirmasi mitra, serta menautkan setiap kartu ke form dengan minat layanan yang sudah terisi; `service_interest` hanya diterima bila slug-nya dikenal dan bertahan setelah submit gagal (`MarketingGrowthTest`). Beranda menampilkan strip terkurasi dengan tautan "Lihat semua N tema" — tema di luar strip sengaja diuji tidak ada di beranda (`LandingAndDiscoveryTest`).

### Email/notifikasi
- Setiap email transaksional dirender sebagai HTML sekaligus teks biasa, lalu di-assert pada isi yang dilihat penerima: subjek, sapaan, baris label/nilai, tautan aksi, nama produk dan usaha, alamat pos, serta tidak ada sisa direktif Blade (`{{`, `@section`, `@include`). Email tidak lagi memakai markdown bawaan Laravel, sehingga assertion terhadap `introLines`/`greeting` tidak relevan lagi (`MailTemplateTest`).
- Notifikasi yang benar-benar dikirim diuji lewat transport `array`, bukan hanya lewat render manual view. Mailer menyuntikkan datanya sendiri (sebuah `Message` di kunci `message`) tepat sebelum body dirender, dan bentrokan kunci itu pernah mengosongkan kutipan enquiry; bug seperti ini lolos dari test render manual tetapi tertangkap di jalur kirim (`MailTemplateTest`).
- Email verifikasi memakai `VerifyEmailNotification` (turunan `VerifyEmail` bawaan) yang tetap menghasilkan signed URL, dan `User` mengirim kelas itu. Test registrasi, login, dan verifikasi meng-assert kelas tersebut, bukan kelas framework (`AuthenticationTest`, `EmailVerificationTest`).
- Data dinamis ikut diuji: nomor pesanan dan total pembayaran, nomor WhatsApp yang sudah dinormalisasi ke `62…`, jam dalam WIB (bukan UTC), serta nama tamu dan acara pada pengingat RSVP (`NotificationsTest`, `RsvpReminderTest`).
- Formulir bukti transfer di dashboard mengirim `PaymentProofSubmitted` ke kotak studio setelah berkas tersimpan, dan unggahan yang ditolak tidak mengirim apa pun (`BankTransferPaymentTest`). Template-nya diuji seperti notifikasi lain: nominal, pengirim, nama berkas, tautan panel admin, dan kalimat bahwa undangan belum aktif (`MailTemplateTest`).
- Pengalihan kotak masuk diuji pada envelope, bukan hanya pada config: dengan `MAIL_REDIRECT_TO` terisi, penerima email yang dialamatkan ke tamu berubah menjadi kotak studio sementara isi badan email tetap menyebut nama orang yang dimaksud; nilai kosong membiarkan penerima apa adanya (`SandboxMailboxTest`).
- Satu email uji tetap dikirim manual dari masing-masing template sebelum rilis: test membuktikan isi body, bukan bahwa domain pengirim sudah terverifikasi di Resend.

## 3. Quality gates

Sebelum merge:
- Formatter/linter lulus.
- Static analysis/type-check lulus.
- Test yang relevan lulus.
- Build lulus.
- Migrasi diuji.
- Security-sensitive changes ditinjau.
- Dokumentasi diperbarui.

Jangan menonaktifkan test atau mengubah assertion agar build tampak hijau tanpa memahami alasan.

## 4. Test data

- Gunakan data sintetis.
- Jangan memasukkan data pribadi pelanggan ke fixture.
- Test payment provider menggunakan sandbox/mock.
- Test storage menggunakan local emulator/mock yang sesuai.
- Bersihkan data test secara aman.

## 5. Release checks

- Backup berhasil.
- Restore diuji sesuai jadwal.
- Environment variables tersedia.
- Webhook endpoint production diverifikasi.
- Queue worker berjalan.
- Monitoring dan alert aktif.
- Kebijakan retensi cocok dengan scheduled jobs.
- Aset merek tersaji 200 dengan MIME benar: `favicon.ico`, `apple-touch-icon.png`, `icon-192.png`, `icon-512.png`, `og-wedly.png`, dan `site.webmanifest`.
- Rollback plan tersedia.
