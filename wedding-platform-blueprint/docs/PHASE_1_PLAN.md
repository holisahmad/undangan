# Rencana & Hasil Implementasi Fase 1

- Status: Implemented (vertical slice)
- Tanggal: 2026-10-30
- Acuan: `PRD.md` (core), `ARCHITECTURE.md` (modular monolith), `DATABASE_SCHEMA.md`, `SECURITY.md`, `TESTING.md`, `AI_AGENT_RULES.md`
- Keputusan terkait: `AUDIT_REPORT.md`, ADR 004–006

## 1. Ruang lingkup

Alur yang disetujui: **auth → wedding → draft → publish → public page**.

Termasuk:
1. Registrasi, login, logout, reset kata sandi berbasis session (Blade).
2. CRUD undangan milik sendiri (judul internal, slug otomatis).
3. Data mempelai dan acara (waktu disimpan UTC, label waktu mengikuti zona acara).
4. Editor draf: template, judul/greeting/kutipan, cerita, dan fitur tampilan; validasi schema di server.
5. Publikasi snapshot berversi dengan gate entitlement dan audit log; unpublish.
6. Halaman publik `/u/{slug}` dengan state terbit, berakhir, dan dinonaktifkan; rate limit dan whitelist field.
7. Pratinjau draf khusus pemilik (ditandai jelas, tidak dapat diakses tamu).
8. Command operator `entitlement:grant-trial` (audited) selama pembayaran belum ada.

Tidak termasuk (butuh persetujuan terpisah):
- Checkout/pembayaran, order, webhook, perpanjangan.
- Guest management, RSVP, ucapan, tautan personal.
- Media/foto, galeri, arsip, retensi.
- Panel admin, statistik, notifikasi email transaksional.
- Multi-kolaborator, tema kustom, SEO/OG image, analytics.

## 2. Komposisi aplikasi

Direktori: `web/` (Laravel 13.39.0). Dokumen blueprint tetap di `wedding-platform-blueprint/`.

**Domain & application**
- `app/Enums/*` — `UserRole`, `WeddingLifecycleStatus`, `EntitlementType`, `EntitlementStatus`.
- `app/Models/*` — `Wedding`, `WeddingPartner`, `WeddingEvent`, `InvitationTemplate`, `TemplateVersion`, `InvitationDraft`, `InvitationPublication`, `ServiceEntitlement`, `AuditLog`, `User`.
- `app/Actions/Weddings/*` — `CreateWedding`, `UpdateWedding`.
- `app/Actions/Invitations/*` — `SaveInvitationDraft`, `PublishInvitation`, `UnpublishInvitation`.
- `app/Actions/Entitlements/GrantInvitationEntitlement`.
- `app/Support/Templates/*` — `TemplateRegistry` (renderer code-only), `InvitationContentValidator` (schema server-side).
- `app/Support/Invitations/InvitationSnapshot` — snapshot beku, format versi 1, label waktu.
- `app/Support/Audit/AuditLogger`, `app/Support/Weddings/WeddingSlug`.
- `app/Data/PublicInvitation` — DTO whitelist untuk halaman publik.
- `app/Exceptions/InvitationNotPublishable`.

**HTTP**
- `app/Http/Controllers/Auth/*` — register, session, forgot/reset password.
- `app/Http/Controllers/{Dashboard,Wedding,WeddingPartner,WeddingEvent,InvitationDraft,InvitationPublication,InvitationPreview,PublicInvitation}Controller`.
- `app/Http/Requests/*` — `StoreWeddingRequest`, `UpdateWeddingRequest`, `PartnerRequest`, `EventRequest`, `InvitationDraftRequest`, `Auth/LoginRequest`.
- `app/Policies/WeddingPolicy` (owner-only write, admin dapat melihat; UUID bukan otorisasi).
- `routes/web.php`, `app/Providers/AppServiceProvider` (rate limiter `public-invitation`, password defaults, HTTPS di produksi).

**Persistence**
- 9 migrasi: `users.role`, `weddings`, `wedding_partners`, `wedding_events`, `invitation_templates`, `template_versions`, `invitation_drafts`, `invitation_publications`, `service_entitlements`, `audit_logs`.
- Factory untuk semua aggregate, `TemplateSeeder`, `DatabaseSeeder` (demo user hanya `local`).

**Presentasi**
- `resources/views/layouts/{app,guest}`, `partials/assets` (fallback sebelum build).
- `auth/*`, `dashboard`, `weddings/{index,create,show,edit}`, `weddings/partials/{card,partner-fields,event-fields}`.
- `invitations/templates/classic-ivory` (halaman publik mandiri, CSS inline), `invitations/unavailable`.
- `welcome` diganti menjadi landing minimal (bukan default Laravel).

**Operasional**
- `tools/packagist-proxy.php` (dev-only, jembatan metadata Composer; lihat `AUDIT_REPORT.md`).
- `docker-compose.yml` (PostgreSQL 17 untuk produksi/parity), `web/.env.postgres.example`.

## 3. Acceptance criteria dan bukti test

| Kriteria | Bukti |
| --- | --- |
| Registrasi membuat akun `customer` dan login; email duplikat ditolak | `AuthenticationTest` |
| Login/logout, kredensial salah ditolak, halaman privat menolak tamu | `AuthenticationTest` |
| Reset password: tautan terkirim, token valid mengubah password, token palsu ditolak | `PasswordResetTest` |
| Pemilik dapat melihat/mengedit; customer lain mendapat 403 walau memegang UUID | `WeddingIsolationTest` |
| Index hanya menampilkan undangan milik sendiri | `WeddingIsolationTest` |
| Resource anak (partner) dari undangan lain tidak dapat diubah (404) | `WeddingIsolationTest` |
| Simpan draf menaikkan revisi dan menyimpan payload tervalidasi | `InvitationDraftTest` |
| Payload yang melanggar schema ditolak tanpa mengubah draf | `InvitationDraftTest` |
| Template versi non-published ditolak | `InvitationDraftTest` |
| Publish tanpa entitlement ditolak; tanpa mempelai/acara ditolak | `PublishInvitationTest` |
| Publish lengkap membuat snapshot v1 + audit log; expiry mengikuti entitlement | `PublishInvitationTest` |
| Perubahan draf setelah publish tidak mengubah snapshot | `PublishInvitationTest` |
| Publish ulang membuat versi 2 sebagai publikasi aktif | `PublishInvitationTest` |
| Hanya pemilik dapat publish/unpublish | `PublishInvitationTest` |
| Unpublish menonaktifkan tautan publik + audit log | `PublishInvitationTest` |
| Slug tidak dikenal / belum pernah terbit → 404 | `PublicInvitationTest` |
| Halaman publik membaca snapshot, tidak membocorkan judul internal | `PublicInvitationTest` |
| Publikasi berakhir → 410 tanpa data mempelai; nonaktif → 410 | `PublicInvitationTest` |
| Halaman publik di-rate-limit per IP (429) | `PublicInvitationTest` |
| Command trial entitlement menulis entitlement + audit; slug tidak dikenal gagal | `EntitlementCommandTest` |
| Pratinjau hanya untuk pemilik; tamu diarahkan ke login, customer lain 403 | `InvitationPreviewTest` |
| Pratinjau menampilkan konten draf yang belum terbit dengan banner; URL publiknya tetap 404 | `InvitationPreviewTest` |
| Suite menolak berjalan jika driver database berbeda dari `EXPECTED_DB_DRIVER` (guard parity CI) | `DatabaseDriverTest` |

## 4. Perintah dan hasil aktual

Dijalankan di `web/` pada 2026-10-30:

```powershell
php artisan migrate:fresh --seed
# 13 migrasi DONE (termasuk 9 migrasi domain), TemplateSeeder DONE

php artisan test
# Tests: 32 passed (110 assertions), Duration 5.58s

php vendor\bin\pint --quiet
# exit 0 (seluruh file diformat)

npm install --no-audit --no-fund
# 87 packages

npm run build
# vite v7.3.7, 61 modules, public/build/manifest.json + assets dibuat

$env:COMPOSER_HOME='d:\project-code\www\undangan\.local\composer'; composer audit
# No security vulnerability advisories found
```

`php artisan test` dijalankan ulang setelah Pint: tetap **32 passed**.

Penambahan setelah tinjauan awal (masih fase 1, tanpa fitur baru):

```powershell
php artisan test --filter=InvitationPreviewTest
# Tests: 4 passed (13 assertions)

php artisan test
# Tests: 1 skipped, 36 passed (123 assertions)  -> 37 test total

php vendor\bin\pint --test
# PASS 101 files

# guard driver database (dipakai job CI PostgreSQL)
$env:EXPECTED_DB_DRIVER='sqlite'; php artisan test --filter=DatabaseDriverTest
# 1 passed
$env:EXPECTED_DB_DRIVER='pgsql';  php artisan test --filter=DatabaseDriverTest
# 1 failed: expected 'pgsql', actual 'sqlite'  (guard bekerja)
```

## 5. Catatan implementasi dan penyimpangan

1. **Laravel 13, bukan 12** — ADR 004.
2. **SQLite lokal, PostgreSQL produksi** — ADR 005; compose disertakan tetapi belum dijalankan karena Docker tidak terpasang.
3. **Jembatan Composer lokal** — mirror Aliyun kedaluwarsa; `tools/packagist-proxy.php` + `.local/composer/config.json` dipakai untuk instalasi. `web/composer.json` tidak bergantung pada proxy.
4. **Symfony dipatok ke 7.4 LTS** karena PHP 8.4.0 < 8.4.1; audit bersih.
5. **Route publik `/u/{slug}`**, bukan `/public/invitations/{slug}` — ADR 006.
6. **Waktu acara** disimpan UTC, label tampil dihitung pada zona acara saat publish — ADR 006.
7. **Tidak ada Livewire/SPA**; state publikasi dikelola server agar mudah diuji dan diaudit.
8. **Pint** memformat ulang line ending dan beberapa file skeleton (`bootstrap/providers.php`, `config/auth.php`, `database/factories/UserFactory.php`); tidak ada perubahan perilaku.
9. **Preview** memakai renderer yang sama dengan halaman publik, dengan banner khusus; tidak dapat diakses tanpa login dan otorisasi.
10. **CI** ditulis di `.github/workflows/ci.yml` (quality, tests-sqlite, tests-postgres) tetapi **belum pernah dieksekusi**: workspace belum berupa repositori git dan Docker tidak tersedia di mesin ini. YAML divalidasi dengan parser, bukan dengan menjalankan workflow.
11. **Guard driver database** (`DatabaseDriverTest`) membaca `EXPECTED_DB_DRIVER`; nilainya kosong di lokal sehingga test di-skip, dan diisi oleh workflow CI. Tujuannya mencegah job PostgreSQL diam-diam menguji SQLite karena `phpunit.xml` menetapkan `DB_CONNECTION=sqlite`.

## 6. Risiko & pekerjaan tersisa

- Uji parity terhadap PostgreSQL nyata belum dijalankan di mesin ini (tanpa Docker/`pdo_pgsql`); workflow CI menyediakannya tetapi belum pernah dieksekusi.
- CI sudah didefinisikan, belum terbukti hijau; job PostgreSQL mungkin perlu triase pada eksekusi pertama.
- Rate limiter bergantung pada cache; produksi harus memakai cache bersama.
- Belum ada verifikasi email, pembatasan percobaan login lintas akun, atau 2FA.
- Belum ada observability (log terstruktur, error tracking).
- Belum ada kebijakan redirect bila slug berubah; slug dibekukan.
- Konten draf divalidasi schema, tetapi belum ada sanitasi HTML kaya (saat ini teks + `nl2br`).

## 7. Langkah berikutnya (usulan, butuh persetujuan)

1. ~~CI: Pint check + PHPUnit (SQLite dan PostgreSQL service) + `composer audit` + `npm run build`.~~ Ditulis di `.github/workflows/ci.yml`; tinggal dijalankan pada repositori git (perlu `git init` + remote).
2. Fase order/pembayaran: order, order item, webhook idempoten, entitlement otomatis, audit admin.
3. Guest management: daftar tamu, impor CSV aman, tautan personal, RSVP, ucapan.
4. Media: upload intent, storage object, signed URL, kuota, retensi.
5. Admin minimum: daftar wedding, lifecycle override, audit log viewer.
