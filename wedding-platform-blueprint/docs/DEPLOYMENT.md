# Deployment & Operations

## 1. Environments

Pisahkan:
- Local development.
- Test/CI.
- Staging.
- Production.

Jangan menggunakan database atau bucket production untuk test lokal.

## 2. Configuration

Environment variables/secrets minimal mencakup:
- Database credentials.
- Session/auth secrets.
- Object storage credentials.
- Payment provider keys.
- Webhook secrets.
- Email provider keys.
- Queue/cache config.
- Application URL.
- Logging/error tracking keys.

Gunakan secret manager atau environment deployment. Jangan commit `.env` atau credential.

### 2.1 Wajib untuk produksi (hardening)

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` HTTPS.
- `SESSION_ENCRYPT=true`; cookie `Secure` aktif otomatis di production. Set `SESSION_SECURE_COOKIE=false` hanya bila sengaja menjalankan mode production di atas HTTP lokal.
- `ADMIN_REQUIRE_TWO_FACTOR=true` setelah operator pertama mendaftarkan autentikator di `/pengaturan/keamanan`. Tanpa flag ini panel admin tetap dapat dibuka dengan kata sandi saja.
- `SECURITY_CSP_REPORT_ONLY=true` saat rilis pertama. Tinjau `storage/logs/laravel.log` (event `content_security_policy.violation`) selama minimal satu siklus rilis, perbaiki sumber pelanggaran (termasuk inline script), lalu ubah ke `false` agar kebijakan ditegakkan — flag tersebut mengganti header ke `Content-Security-Policy`, bukan menghapusnya.
- `MEDIA_SCAN_COMMAND` diisi bila host menyediakan clamdscan/ClamAV; biarkan kosong hanya bila risiko unggahan sudah diterima secara eksplisit. Pertahankan `MEDIA_SCAN_FAIL_CLOSED=true`.
- `PAYMENT_GATEWAY=bank_transfer` dan `PAYMENT_BANK_ACCOUNTS` berisi rekening resmi (format `bank|nomor|pemilik`, dipisah `;`). `PAYMENT_WINDOW_HOURS` default 24. Sandbox `PAYMENT_FAKE_ENABLED` sudah `false` sebagai bawaan dan tetap ditolak di produksi; `PAYMENT_FAKE_WEBHOOK_SECRET` sengaja kosong di deployment — webhook sandbox tidak dapat menandatangani maupun memverifikasi apa pun tanpa nilai eksplisit (diisi hanya untuk demo lokal).
- `PAYMENT_PROOF_DISK=local` menyimpan bukti transfer di disk privat `storage/app/private`. Bila memakai S3, set disk privat terpisah dan pastikan tidak ada bucket policy publik; bukti transfer adalah dokumen pribadi pelanggan.
- `php artisan schedule:run` aktif lewat cron dan mencakup `payments:expire` (tiap 5 menit) serta pengingat RSVP. Tanpa scheduler, kode pembayaran kedaluwarsa tidak pernah ditandai.
- `MAIL_MAILER=resend` dengan `RESEND_API_KEY` dan `MAIL_FROM_ADDRESS` memakai domain terverifikasi (SPF + DKIM dicatat di DNS). `onboarding@resend.dev` hanya boleh mengirim ke alamat pemilik akun Resend, sehingga alamat itu tidak akan lolos saat pelanggan asli mendaftar. `APP_URL` harus URL publik: tautan di badan email dan tombol mengambil host dari situ.
- Selama `MAIL_FROM_ADDRESS` masih `onboarding@resend.dev`, isi `MAIL_REDIRECT_TO` dengan kotak masuk pemilik akun Resend. `App\Support\Mail\SandboxMailbox` memanggil `Mail::alwaysTo()` saat boot, jadi seluruh email dikirim ke satu kotak itu (Cc/Bcc dibuang) dan tidak ada pengiriman yang ditolak provider; badan email tetap menyebut nama pelanggan atau tamu yang dimaksud. Variabel ini wajib dikosongkan bersamaan dengan penggantian `MAIL_FROM_ADDRESS` ke domain terverifikasi, kalau tidak pelanggan tidak pernah menerima verifikasi maupun bukti pembayaran mereka sendiri.
- Kirim satu email uji ke kotak masuk sungguhan dari masing-masing template (verifikasi, pembayaran, bukti transfer, enquiry, suspend, pengingat RSVP) sebelum rilis; template HTML bisa ter-render benar di test dan tetap gagal di jalur kirim karena data yang disuntikkan mailer.
- Pasang OPcache (dan Brotli/CDN bila ada) di depan aplikasi; aset Vite sudah ber-hash, sedangkan derivatif media memakai cache immutable satu tahun.
- Pasang error tracking (Sentry atau sejenis), uptime check pada `/up`, dan alert untuk `media:reconcile --strict`.
- Jalankan drill restore PostgreSQL di staging (bukan hanya SQLite) sebelum go-live; prosedur SQLite yang sudah dieksekusi ada di `BACKUP_RESTORE_DRILL.md`.

### 2.2 Batas unggahan di web server

Default PHP (`upload_max_filesize=2M`, `post_max_size=8M`) lebih kecil daripada batas galeri (8 MB/foto, 10 foto), sehingga unggahan berhenti sebagai 413 sebelum aplikasi berjalan. Aplikasi menurunkan batas yang ditampilkan ke nilai host (`App\Support\Media\UploadLimits`), tetapi untuk memakai batas penuh:

- php.ini FPM dan CLI: `upload_max_filesize=16M`, `post_max_size=48M`, `memory_limit=256M`, `max_file_uploads=20`, `max_execution_time=120`.
- nginx: `client_max_body_size 48m;` (Apache FPM tidak punya batas sendiri; mod_security bila ada perlu dikecualikan untuk rute galeri).
- Bila host tidak dapat menyediakan 48 MB, turunkan `MEDIA_MAX_UPLOAD_KB` agar formulir dan server sepakat (mis. 24576 untuk 24 MB sekali kirim).
- `request.post_too_large` di log menandakan pelanggan menabrak batas ini; naikkan batas atau arahkan pelanggan mengunggah bertahap.
- `display_errors=Off` dan `log_errors=On`. PHP mencetak warning `POST Content-Length of ... exceeds the limit ...` saat request startup, sebelum aplikasi berjalan. Bila `display_errors` menyala, warning itu dikirim lebih dulu sehingga header respons sudah terkirim dan halaman 413 tiba sebagai 200 dengan warning di atasnya; ini normal di server pengembangan dan tidak boleh terjadi di produksi.

## 3. Deploy process

1. CI menjalankan lint, type-check, tests, dan build.
2. Review migrasi dan dampak backward compatibility.
3. Backup sebelum perubahan berisiko.
4. Deploy aplikasi.
5. Jalankan migrasi secara terkontrol.
6. Pastikan worker/cron diperbarui.
7. Jalankan smoke tests.
8. Periksa logs, errors, dan job queue.
9. Sediakan rollback plan.

Untuk migrasi besar, gunakan strategi expand-and-contract agar versi aplikasi lama dan baru tidak saling merusak selama deployment.

## 4. Scheduled jobs

- Notifikasi masa aktif.
- Expiry/lifecycle.
- Retention/deletion.
- Storage reconciliation.
- Payment reconciliation:
  - `payments:expire --limit=200` menutup jendela transfer yang lewat tenggat (tiap 5 menit, `withoutOverlapping`).
  - Cocokkan antrean `payment_confirmations` berstatus `submitted` dengan mutasi rekening setiap hari kerja; antrean yang menua adalah risiko operasional, bukan otomatis.
- Cleanup archive ZIP.
- Failed job alerts.

Job harus idempotent dan memiliki observability.

## 5. Backup and recovery

- Backup PostgreSQL terjadwal.
- Tentukan RPO/RTO berdasarkan kebutuhan bisnis.
- Enkripsi backup dan batasi akses.
- Tentukan apakah object storage memerlukan versioning/replication.
- Uji restore secara berkala.
- Dokumentasikan prosedur pemulihan.
- Periksa interaksi restore dengan data yang telah dihapus sesuai retensi.

## 6. Observability

Pantau:
- Error rate.
- Response time.
- Queue depth.
- Failed jobs.
- Payment webhook failures (hanya bila gateway provider dipakai).
- Umur antrean `payment_confirmations` berstatus `submitted` dan jumlah `payment.window_expired` per hari.
- Upload/processing failures.
- Storage bytes dan egress.
- Database capacity.
- Scheduled job lag.
- Expiry/retention failures.

Log tidak boleh berisi password, token, signed URL, atau PII yang tidak diperlukan.

## 7. Launch checklist

- Domain dan TLS.
- DNS.
- Production environment.
- Payment: rekening bank resmi terverifikasi di `PAYMENT_BANK_ACCOUNTS`, jendela pembayaran disepakati, dan operator tahu cara memverifikasi bukti transfer.
- Email sender configuration: domain terverifikasi di Resend, `MAIL_FROM_ADDRESS` memakai domain itu, `MAIL_REDIRECT_TO` sudah dikosongkan, SPF/DKIM hijau, dan satu email uji benar-benar sampai ke kotak masuk pelanggan, bukan hanya ke log atau ke kotak studio.
- Object storage policies.
- Backup/restore verified.
- Admin MFA terdaftar dan `ADMIN_REQUIRE_TWO_FACTOR=true`.
- Privacy/terms pages.
- Aset merek tersaji 200: `favicon.ico`, `favicon-32.png`, `apple-touch-icon.png`, `icon-192.png`, `icon-512.png`, `og-wedly.png`, dan `site.webmanifest`. Server harus memetakan `.webmanifest` ke `application/manifest+json` (Apache: `AddType` di `public/.htaccess`; nginx: tambahkan tipe yang sama pada `types`). `theme_color` dan `APP_NAME` ikut diperiksa dari HTML halaman beranda.
- Kontak publik berisi data nyata, bukan placeholder `.test`: `CONTACT_EMAIL`, `CONTACT_PHONE`, `CONTACT_WHATSAPP`, `CONTACT_MAPS_URL`. Alamat pos di `config/site.php` (`address`) harus cocok dengan daftar Google Maps, dan setiap tautan `wa.me` yang tampil memakai format `62…` (bukan `08…`) karena WhatsApp menolak bentuk lokal melalui `App\Support\Contact\PhoneNumber`.
- CSP report-only aktif dan laporan ditinjau.
- Pemindai unggahan aktif atau risiko diterima tertulis.
- Error tracking dan alerts.
- Rate limiting.
- Smoke test mobile.
- Support process.
- Rollback plan.
