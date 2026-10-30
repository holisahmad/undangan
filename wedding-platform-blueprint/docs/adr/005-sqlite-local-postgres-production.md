# ADR 005: SQLite untuk Lokal/Test, PostgreSQL untuk Produksi

- Status: Accepted
- Date: 2026-10-30

## Context

Pilihan awal adalah menjalankan PostgreSQL di Docker. Audit menunjukkan Docker tidak terpasang, ekstensi `pdo_pgsql` tidak tersedia, `extension_dir` PHP tidak ada, dan kredensial service PostgreSQL 17 lokal tidak diketahui. PostgreSQL tetap menjadi target produksi (ADR 002).

## Decision

- **Lokal dan test**: SQLite (`DB_CONNECTION=sqlite`, test memakai `:memory:`).
- **Produksi**: PostgreSQL 17, disiapkan melalui `docker-compose.yml` dan `web/.env.postgres.example`.
- Migrasi wajib portabel:
  - Primary key UUID (`$table->uuid()`), foreign key `foreignUuid`/`foreignId`.
  - `json` untuk payload tervalidasi (draft, snapshot, metadata audit).
  - `enum` hanya untuk nilai tertutup dan pendek.
  - Tanpa fungsi SQL spesifik vendor di application layer.
- Test tidak boleh bergantung pada perilaku SQLite yang tidak ada di PostgreSQL (mis. case-insensitive `LIKE`).

## Consequences

Positive:
- Developer dapat menjalankan aplikasi dan seluruh test tanpa Docker.
- Tidak ada biaya operasional database lokal.
- Migrasi dan query tetap dapat dipindah ke PostgreSQL.

Trade-offs:
- Perbedaan tipe/constraint antara SQLite dan PostgreSQL belum diuji end-to-end pada mesin ini.
- Kolom `timestamp` tanpa timezone di PostgreSQL perlu konvensi aplikasi (lihat ADR 006).
- Perlu CI yang menjalankan test terhadap PostgreSQL sebelum produksi; workflow `.github/workflows/ci.yml` (job `tests-postgres`) sudah menyediakannya tetapi belum dieksekusi di mesin ini.

## Revisit when

- Docker atau PostgreSQL lokal tersedia: jalankan test parity terhadap PostgreSQL (atau jalankan job CI `tests-postgres`).
- Muncul kebutuhan full-text search, concurrent write tinggi, atau constraint lanjutan.
