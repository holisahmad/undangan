# Security Requirements

## 1. Prinsip

- Least privilege.
- Defense in depth.
- Server-side validation.
- Authorization per resource.
- Minimize stored personal data.
- Secrets outside repository.
- Log securely.
- Test security regressions.

## 2. Authentication

- Gunakan library/framework auth yang terawat.
- Password di-hash menggunakan mekanisme modern bawaan framework.
- Rate-limit login, reset password, dan endpoint publik sensitif.
- Token reset memiliki masa berlaku dan hanya dapat digunakan sekali.
- Cookie session, jika digunakan, memiliki flag Secure, HttpOnly, dan SameSite yang sesuai.
- Lindungi CSRF sesuai arsitektur.
- Sediakan logout dan invalidasi sesi yang benar.
- Area pelanggan (`dashboard`, `weddings.*`, `orders.*`, galeri, tamu) dan `/admin` hanya dapat diakses setelah alamat email terverifikasi (`MustVerifyEmail` + middleware `verified`). Tautan verifikasi bertanda tangan, terikat akun, dan di-throttle.

## 3. Authorization / tenant isolation

- Setiap request memverifikasi akses terhadap wedding, guest, media, order, dan archive.
- Jangan percaya ID dari frontend.
- UUID tidak menggantikan authorization.
- Uji IDOR/BOLA dengan dua akun berbeda.
- Admin permissions spesifik dan dibatasi.
- Support tidak otomatis memiliki akses ke media privat.
- Kolom privilese tidak mass-assignable: `users.role` tetap dibutuhkan factory, sedangkan `suspended_at`, `suspension_reason`, dan `two_factor_confirmed_at` ditulis dengan `forceFill()` oleh controller yang memilikinya, sehingga satu `update($request->all())` di masa depan tidak menjadi eskalasi hak akses.

## 4. Input and output

- Validasi input di server.
- Escape output sesuai konteks.
- Cegah XSS pada guestbook dan teks undangan.
- Validasi URL eksternal dan batasi risiko SSRF bila server mengambil URL.
- Batasi panjang teks, jumlah item, dan ukuran payload.
- CSV export harus mencegah formula injection.
- Query database harus menggunakan parameter binding/ORM aman.

## 5. Upload safety

- Batas ukuran dan kuota sebelum upload.
- Validasi ekstensi, MIME yang terdeteksi, dan file signature.
- Jangan mempercayai filename atau Content-Type browser.
- Gunakan object key acak; jangan jadikan filename pengguna sebagai object key.
- Simpan file pending di area non-public.
- Scan/proses media dalam lingkungan yang dibatasi bila tersedia.
- Tolak format yang tidak dibutuhkan.
- Batasi dimensi/resolusi untuk pemrosesan gambar.
- Cegah path traversal dan decompression bombs jika menerima archive.
- Jangan menjalankan file unggahan sebagai kode.
- Rate-limit upload publik.

## 6. Signed URLs

- Berumur pendek.
- Diterbitkan hanya setelah otorisasi.
- Tidak dicatat ke log.
- Jangan disimpan sebagai URL permanen.
- Cabut akses dengan memblokir pembuatan link baru dan, jika provider mendukung, kebijakan akses terkait.

## 7. Payments and webhooks

- Verifikasi signature dan event provider.
- Gerbang provider ditegakkan saat gateway di-resolve: `PaymentGatewayRegistry` menolak provider yang `isEnabled()` bernilai `false`, jadi webhook sandbox menjawab 404 ketika `PAYMENT_FAKE_ENABLED=false` atau di produksi, meski body bertanda tangan benar.
- Secret webhook tidak punya nilai cadangan di konfigurasi; tanpa `PAYMENT_FAKE_WEBHOOK_SECRET` verifikasi gagal (fail-closed) dan tidak ada kunci yang dikenal publik.
- Terapkan idempotency.
- Simpan event ID unik.
- Validasi amount, currency, order reference, dan status.
- Jangan percaya query parameter browser.
- Hindari replay event.
- Jangan menyimpan data kartu sensitif.

## 8. Admin and audit

- Terapkan MFA untuk admin bila didukung.
- Catat actor, action, resource, result, timestamp, request ID.
- Redact PII dan secret dari log.
- Tindakan destruktif memerlukan konfirmasi dan permission khusus.
- Jangan membuat support impersonation tanpa kontrol dan audit.
- Nonaktifkan akun lewat status (`users.suspended_at` + alasan), bukan penghapusan; login ditolak dan sesi aktif berakhir pada permintaan berikutnya. Operator tidak dapat menonaktifkan akun sendiri atau akun operator lain, dan setiap suspend/aktivasi tercatat sebagai `user.suspended`/`user.activated`.
- Pengingat RSVP hanya dapat dipicu pemilik undangan yang lolos otorisasi `wedding` atau scheduler internal, hanya menyasar tamu yang memberi email dan belum menjawab, dan menghormati jeda 7 hari per tamu. Setiap pengingat mencabut tautan personal lama dan menerbitkan tautan baru; aktivitasnya dicatat sebagai `guest.reminders_sent` tanpa token maupun alamat email di metadata.
- CRM pelanggan (`users.crm_note`, `users.crm_tags`) hanya dapat ditulis operator. Perubahan dicatat sebagai `user.crm_updated`; isi catatan tidak disalin ke metadata audit, hanya penanda ada/tidak, karena catatan bebas berpotensi memuat data pribadi.
- Ekspor direktori pelanggan memakai filter yang sama dengan panel, melewati `CsvSanitizer` untuk setiap sel (formula spreadsheet tidak dieksekusi), dan dicatat sebagai `user.exported` beserta jumlah baris serta filter yang dipakai tanpa menyalin email ke metadata.
- Portofolio/showcase hanya tampil setelah pemilik mengaktifkannya dan undangan masih punya publikasi aktif; halaman publik portofolio memakai snapshot terbit, memasang bilah penanda permanen, dan tidak memasang formulir RSVP maupun galeri. Setiap perubahan dicatat sebagai `wedding.showcase_enabled`/`wedding.showcase_disabled`.
- Testimoni wajib ditulis pemilik undangan yang undangannya terbit; semua kiriman masuk `pending` dan hanya tampil setelah operator menyetujui (`testimonial.approved`/`testimonial.rejected`). Menyunting testimoni yang sudah disetujui mengembalikannya ke `pending`.
- Tabel `analytics_events` hanya menyimpan nama event, `wedding_id` opsional, properti non-personal (mis. `surface`, `status`), dan waktu. Tidak ada user id, IP, user agent, atau cookies pelacak; kegagalan pencatatan dilaporkan ke log tanpa menggagalkan permintaan.
- Autentikasi dua faktor (TOTP RFC 6238) tersedia untuk semua akun di `/pengaturan/keamanan`. Hanya `two_factor_confirmed_at` yang dianggap aktif, sehingga penyiapan yang belum dikonfirmasi tidak pernah mengunci akun. Secret disimpan terenkripsi (cast `encrypted`), kode pemulihan disimpan sebagai hash SHA-256 sekali pakai dan dihapus saat dipakai, dan percobaan verifikasi dibatasi 5/menit per akun+IP. Semua perubahan dicatat sebagai `user.two_factor_enabled`/`user.two_factor_disabled`.
- Login dua langkah tidak membuat sesi walaupun kata sandi benar: kata sandi hanya membuktikan faktor pertama, sesi dimulai setelah kode TOTP atau kode pemulihan lolos (`auth.two_factor_passed`). `ADMIN_REQUIRE_TWO_FACTOR=true` mewajibkan operator mendaftarkan autentikator sebelum panel admin terbuka; nilai default `false` agar instalasi baru tidak terkunci sebelum operator pertama mendaftar.

## 9. Rate limiting and abuse

Terapkan batas untuk:
- Login/reset.
- RSVP dan guestbook.
- Recipient token lookup.
- Upload.
- Archive generation.
- Public page endpoints jika perlu.
- Contact forms.

Implementasi saat ini: login/reset memakai throttle bawaan Laravel, pendaftaran `register` (5/menit per IP, `REGISTER_RATE_LIMIT`), undangan publik `public-invitation`, tautan personal `guest-invitation`, RSVP `guest-rsvp`, galeri publik `public-gallery`, webhook pembayaran `120/menit`, formulir kontak `lead-capture` (6/menit per IP, `MARKETING_LEAD_RATE_LIMIT`), dan laporan CSP `csp-reports` (60/menit). Formulir kontak juga memakai honeypot `website`: isian yang terisi dibalas dengan pesan sukses palsu tanpa menyimpan baris apa pun. Verifikasi 2FA dibatasi 5 percobaan/menit per akun+IP (`TWO_FACTOR_RATE_LIMIT`).

Tambahkan batas kuota per acara dan deteksi lonjakan penggunaan.

## 10. Data protection

- TLS untuk transit.
- Enkripsi storage/provider bila tersedia.
- Backup terlindungi.
- Akses production dibatasi.
- Retensi dan penghapusan terdokumentasi.
- Prosedur insiden dan notifikasi mengikuti kewajiban yang berlaku.
- Session payload dienkripsi (`SESSION_ENCRYPT=true`) dan cookie otomatis berflag `Secure` saat `APP_ENV=production` (dapat ditimpa `SESSION_SECURE_COOKIE` untuk lokal tanpa HTTPS).
- CSP dikirim dalam mode report-only beserta `Reporting-Endpoints`; laporan dikirim browser ke `POST /csp-reports`, dipotong, dan hanya masuk log. Penegakan dilakukan setelah laporan ditinjau (`SECURITY_CSP_REPORT_ONLY=false`): flag tersebut mengganti header ke `Content-Security-Policy`, bukan menghilangkan kebijakan sama sekali, jadi seluruh inline script harus sudah dibereskan lebih dulu.
- Unggahan dapat melewati pemindai eksternal (`MEDIA_SCAN_COMMAND`, mis. clamdscan). Keluaran `FOUND` atau kegagalan pemindai menolak berkas; `MEDIA_SCAN_FAIL_CLOSED=true` membuat kegagalan pemindai tidak pernah meloloskan berkas yang belum diperiksa.
- Derivatif media (thumbnail/preview) memakai objek yang tidak pernah ditulis ulang, sehingga disajikan `private, max-age=31536000, immutable`; original tetap diunduh sebagai attachment tanpa cache panjang.

## 11. Security test checklist

- IDOR/BOLA antar pelanggan.
- Privilege escalation.
- XSS pada teks undangan/ucapan.
- CSRF jika relevan.
- File upload spoofing.
- Signed URL expiry.
- Webhook replay.
- Webhook provider yang tidak aktif: payload sandbox bertanda tangan benar harus tetap ditolak saat sandbox dimatikan, dan tanda tangan bersecret kosong harus gagal.
- Header keamanan pada kedua mode CSP (report-only dan enforcement).
- Race condition kuota.
- Archive download authorization.
- Secret leakage.
- Rate limit bypass.
