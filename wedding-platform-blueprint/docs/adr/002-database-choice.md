# ADR 002: PostgreSQL as Primary Database

- Status: Proposed
- Date: 2026-10-30

## Context

Platform memerlukan relasi konsisten untuk pengguna, acara, tamu, pembayaran, entitlement, media metadata, dan audit.

## Decision

Gunakan PostgreSQL sebagai database utama, kecuali audit repository dan batasan hosting menunjukkan alasan kuat untuk alternatif lain.

## Consequences

- Relasi dan constraint dapat ditegakkan di database.
- Transaksi mendukung proses order dan entitlement.
- JSONB tersedia untuk konfigurasi fleksibel, tetapi bukan pengganti desain relasional.
- Backup dan migrasi perlu menjadi bagian proses release.

## Revisit when

Hanya jika ada batasan teknis/operasional terukur atau kebutuhan produk yang tidak dapat dipenuhi secara layak.
