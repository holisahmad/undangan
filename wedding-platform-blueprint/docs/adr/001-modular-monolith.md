# ADR 001: Modular Monolith

- Status: Proposed
- Date: 2026-10-30

## Context

Platform mencakup undangan, template, guest management, pembayaran, media, arsip, dan admin. Pada tahap awal, domain-domain ini perlu berkembang cepat dengan biaya operasional yang rendah.

## Decision

Mulai dengan modular monolith. Pisahkan modul berdasarkan domain dan jaga kontrak internal. Background workers boleh menjadi proses deployment terpisah tanpa memecah semua domain menjadi microservices.

## Consequences

Positive:
- Deployment lebih sederhana.
- Transaksi database lebih mudah.
- Debugging dan local development lebih ringan.
- Biaya operasional awal lebih rendah.

Trade-offs:
- Batas modul harus dijaga melalui code review.
- Beberapa modul dapat memerlukan pemisahan di masa depan jika skala/organisasi membenarkan.

## Revisit when

- Bottleneck terukur memerlukan scaling independen.
- Tim berbeda membutuhkan ownership dan deployment independen.
- Batas domain sudah stabil dan biaya pemisahan masuk akal.
