# ADR 003: Object Storage for Media

- Status: Proposed
- Date: 2026-10-30

## Context

Foto asli, thumbnail, dan ZIP arsip dapat menghabiskan storage dan bandwidth. Menyimpan binary besar di database utama meningkatkan beban dan biaya backup.

## Decision

Simpan media di object storage yang mendukung private objects dan signed URLs. Simpan object key, metadata, status, ukuran, dan relasi di PostgreSQL.

## Consequences

- Upload/download dapat dilakukan langsung dengan signed URL.
- Perlu menangani orphan objects dan metadata yang tidak sinkron.
- Lifecycle rules provider harus selaras dengan kebijakan retensi produk.
- Restore database saja tidak menjamin semua media pulih; backup/replication media perlu diputuskan.

## Revisit when

Provider tidak memenuhi kebutuhan keamanan, biaya, lokasi data, atau reliabilitas.
