# Audit Repository & Keputusan Teknologi

Tanggal audit: 30 Oktober 2026
Ruang lingkup: `D:\project-code\www\undangan` dan `wedding-platform-blueprint/docs`.

## 1. Kondisi repository saat audit

| Aspek | Temuan |
| --- | --- |
| Isi awal | Hanya dokumen di `wedding-platform-blueprint/` (README, PRD, ARCHITECTURE, DATABASE_SCHEMA, API_SPEC, SECURITY, TESTING, ROADMAP, ADR 001–003, dll). Tidak ada kode aplikasi. |
| Git | `git status` gagal: `fatal: not a git repository`. Tidak ada riwayat, branch, atau perubahan pengguna yang perlu dipertahankan. |
| Kode yang harus dipertahankan | Tidak ada. Satu-satunya artefak adalah dokumen blueprint yang **tidak diubah**. |
| Konvensi wajib | `docs/AI_AGENT_RULES.md` (otorisasi per resource, UUID bukan otorisasi, draft ≠ publication, validasi server-side, audit tindakan sensitif, rate limit publik, tenant isolation). |

## 2. Lingkungan yang tersedia (terverifikasi)

| Komponen | Versi / status |
| --- | --- |
| OS / shell | Windows 26H2, PowerShell (tanpa operator `&&`) |
| PHP | 8.4.0 (Herd Lite), ekstensi `pdo_sqlite` tersedia |
| Composer | 2.8.12 |
| Node / npm | v25.9.0 / 11.21.1 (tanpa pnpm/yarn) |
| PostgreSQL | Client 17.11, service berjalan, tetapi kredensial `postgres` tidak diketahui |
| Docker | Tidak terpasang |
| `pdo_pgsql` | Tidak tersedia; `extension_dir` `C:\php\ext` tidak ada |
| Jaringan | `repo.packagist.org` diblokir/timeout; `packagist.org`, GitHub, dan registry npm dapat diakses |

Implikasi: PostgreSQL tidak dapat dijalankan lokal pada mesin ini. Pengembangan dan test memakai SQLite; PostgreSQL disiapkan sebagai target produksi (lihat ADR 005 dan `docker-compose.yml`).

## 3. Konflik dokumen vs kondisi nyata

1. **Laravel 12 vs 13.** Dokumen tidak mengunci versi. Pilihan pengguna adalah Laravel 12, tetapi skeleton 12.10.0 sudah melewati masa bug-fix pada Oktober 2026 dan mirror yang dipakai menyajikan metadata kedaluwarsa. Diputuskan Laravel 13.39.0 (lihat ADR 004).
2. **PostgreSQL in Docker vs kemampuan mesin.** Docker tidak terpasang dan `pdo_pgsql` tidak ada, sehingga opsi awal tidak dapat dijalankan. Diputuskan SQLite untuk lokal/test dengan migrasi portabel dan compose file untuk produksi (ADR 005).
3. **Mirror Aliyun.** Metadata Aliyun berhenti pada 2025-09-01 (tidak memuat `laravel/tinker ^3`, tag `phar-io/manifest`, `myclabs/deep-copy`), sehingga instalasi gagal. Sebagai gantinya dipakai jembatan metadata lokal ke Packagist resmi (`tools/packagist-proxy.php`) dengan konfigurasi Composer yang hanya berlaku di mesin ini (`.local/composer`). `web/composer.json` tetap portabel.
4. **Timezone acara.** `DATABASE_SCHEMA.md` mencantumkan timezone sebagai pertanyaan terbuka. Diputuskan: simpan UTC, tampilkan memakai timezone acara (ADR 006).
5. **Slug setelah publikasi.** Dokumen menanyakan apakah slug dapat berubah setelah publikasi. Fase 1: slug tidak diedit dari UI; judul internal boleh berubah kapan saja.
6. **Auth API vs web session.** `API_SPEC.md` memakai prefix `/auth/*` sebagai kontrak konseptual. Implementasi fase 1 memakai route web session (`/login`, `/register`, dst.) sesuai stack Blade; kontrak JSON menyusul bila API publik dibutuhkan.
7. **Publikasi bersifat snapshot.** `PublishInvitation` membekukan data ke `published_snapshot`; perubahan draft setelahnya tidak mengubah halaman publik. Ini invariant utama yang diuji.

## 4. Risiko teknis & keputusan tertunda

| Risiko | Dampak | Mitigasi fase 1 |
| --- | --- | --- |
| PHP 8.4.0 < 8.4.1 | Symfony 8.1 (patch CVE) tidak dapat dipasang | `conflict` di `composer.json` mengunci `symfony/http-foundation` & `symfony/mime` ke 7.4.22 (patch); `composer audit` bersih |
| Tanpa Docker/PG lokal | Perbedaan perilaku SQLite vs PostgreSQL | Migrasi memakai tipe portabel (uuid char(36), enum sebagai check constraint di SQLite, json), test memakai SQLite `:memory:`; parity dijadwalkan di job CI `tests-postgres` (`postgres:17-alpine`) dengan guard `DatabaseDriverTest` |
| Registry Composer diblokir | Build ulang di mesin lain bisa gagal | Proxy dev terdokumentasi; `composer.json` tetap memakai Packagist resmi |
| Belum ada pembayaran | Publish tidak bisa dilakukan customer sungguhan | `entitlement:grant-trial` (audited) untuk operator; gate entitlement tetap server-side |
| Rate limit cache default | Limit publik tidak konsisten antar node | Konfigurasi `PUBLIC_INVITATION_RATE_LIMIT`; produksi wajib cache bersama (Redis/database) |
| Media belum ada | Thumbnail template kosong | Template hanya menampilkan tipografi; tidak ada klaim fitur media |
| Email reset memakai `MAIL_MAILER=log` | Tautan reset tidak terkirim nyata | Cukup untuk fase 1; konfigurasi SMTP adalah keputusan deployment |

## 5. Keputusan teknologi final

- **Backend**: Laravel 13.39.0 (PHP 8.4), modular monolith per ADR 001.
- **Presentasi**: Blade server-rendered + Tailwind CSS 4/Vite 7. Tanpa SPA/Livewire agar state publikasi tetap di server dan test HTTP sederhana.
- **Database**: SQLite (lokal/test), PostgreSQL 17 (produksi). Eloquent + migration portabel.
- **Test**: PHPUnit 12, SQLite `:memory:`, `RefreshDatabase`.
- **Queue/cache/session**: driver database (default skeleton), dapat diganti Redis di produksi.
- **Formatter**: Laravel Pint.
- **Belum dipilih / ditunda**: provider pembayaran, object storage, email transaksional, panel admin, observability. Keputusan ini memerlukan persetujuan produk dan tidak diimplementasikan pada fase 1.

## 6. Dampak pada rencana fase 1

Vertical slice yang disetujui: **auth → wedding → draft → publish → public page**. Rincian file, acceptance criteria, dan hasil test ada di `PHASE_1_PLAN.md`.
