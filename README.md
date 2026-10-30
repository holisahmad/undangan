# Undangan — Wedding Invitation Platform

Implementasi fase 1 dari blueprint di `wedding-platform-blueprint/`. Ruang lingkup saat ini adalah vertical slice yang disetujui:

**auth → wedding → draft → publish → halaman publik**

Status: fase 1 selesai dan teruji lokal. Fitur pembayaran, tamu/RSVP, media, dan admin **belum** diimplementasikan.

## Struktur

```
wedding-platform-blueprint/   Dokumen produk, arsitektur, schema, ADR (tidak diubah)
web/                          Aplikasi Laravel 13
  app/                        Domain, actions, HTTP, policies, support
  database/migrations/        Schema portabel (SQLite lokal, PostgreSQL produksi)
  resources/views/            Blade: dashboard, editor, halaman publik
  tests/Feature/              Test auth, isolasi tenant, draft, publish, publik
tools/packagist-proxy.php     Utilitas dev untuk mirror Composer (lihat catatan)
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

### Memberi masa aktif tanpa pembayaran

Publish dijaga oleh entitlement. Selama fase order/pembayaran belum ada, operator dapat memberi masa aktif yang tercatat di audit log:

```powershell
php artisan entitlement:grant-trial {slug-atau-uuid} --months=12 --actor=admin@example.test
```

Tautan publik: `http://localhost:8000/u/{slug}`.

## Test dan kualitas

```powershell
cd web
php artisan test              # 37 test: 36 lulus, 1 skip khusus CI
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
Cabang `main` diasumsikan sebagai default branch.

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
- `wedding-platform-blueprint/docs/PHASE_1_PLAN.md` — ruang lingkup, acceptance criteria, hasil test aktual.
- `wedding-platform-blueprint/docs/adr/004-laravel-13.md`
- `wedding-platform-blueprint/docs/adr/005-sqlite-local-postgres-production.md`
- `wedding-platform-blueprint/docs/adr/006-public-link-and-timezone.md`

## Batasan yang diketahui

- Belum ada pembayaran/order/webhook; entitlement hanya lewat command operator.
- Belum ada guest management, RSVP, ucapan, atau tautan personal.
- Belum ada media/foto, galeri, arsip, dan retensi.
- Belum ada panel admin.
- CI sudah didefinisikan di `.github/workflows/ci.yml` tetapi belum pernah dijalankan: workspace ini belum berupa repositori git dan Docker tidak tersedia.
- Slug tidak dapat diubah setelah dibuat; belum ada redirect.
- Email reset memakai mail driver `log` pada lokal.
