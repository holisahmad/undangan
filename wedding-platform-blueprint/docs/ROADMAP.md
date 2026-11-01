# Implementation Roadmap

## Prinsip

Roadmap adalah urutan validasi dan delivery, bukan alasan membangun semua modul sekaligus. Setiap fase harus selesai berdasarkan acceptance criteria. Durasi adalah estimasi awal untuk tim kecil dan perlu disesuaikan setelah audit repo.

Status implementasi per 2026-10-30: Phase 2–5 selesai pada tingkat kode dan teruji lokal (SQLite). Phase 6 sebagian besar selesai (QA mobile/browser dan pilot pelanggan belum dapat dieksekusi tanpa browser/pelanggan nyata). Phase 7 hanya share link dan ringkasan RSVP yang dikerjakan; item lain ditunda sampai ada bukti pelanggan. Rincian bukti ada di `PHASE_2_3_REPORT.md`, `PHASE_4_REPORT.md`, dan `PHASE_5_7_REPORT.md`; keputusan media ada di `adr/009-media-storage-and-archive.md`, keputusan hardening/growth ada di `adr/010-beta-hardening-and-growth-scope.md`.

## Phase 0 — Discovery & business validation (minggu 1–2)
- Wawancara calon pelanggan.
- Riset pesaing, harga, dan fitur.
- Pilih segmen awal.
- Validasi positioning dan paket.
- Estimasi biaya storage, bandwidth, payment, dan support.
- Definisikan KPI baseline.

Deliverables: `BUSINESS_MODEL.md` final, scope MVP, pricing hypothesis, riset dan keputusan.

Exit criteria: target pelanggan dan masalah utama jelas; asumsi yang belum terbukti ditandai.

## Phase 1 — Architecture & UX (minggu 3–4)
- Audit repository dan kemampuan tim.
- Finalisasi stack melalui ADR.
- ERD dan state transitions.
- Wireframe dan design system.
- Prototype alur pembuatan undangan.
- Threat model dan kebijakan masa aktif.

Exit criteria: prototype dapat diuji; data model dan kontrak utama disepakati.

## Phase 2 — Core invitation MVP (minggu 5–8)
- Auth dan dashboard.
- Wedding/event CRUD.
- Template registry dan 3–5 template.
- Editor draft.
- Preview.
- Publication snapshots.
- Public invitation route.
- Masa aktif dasar.
- Admin dasar.
- Automated tests.

Exit criteria:
- Pengguna dapat membuat, preview, dan publish.
- Draft tidak mengubah publikasi aktif sebelum publish.
- Tenant isolation test lulus.
- Halaman publik mobile-friendly.

## Phase 3 — Orders and payments (minggu 9–10)
- Product/plan configuration.
- Orders and order items.
- Payment sandbox integration.
- Verified webhook and idempotency.
- Entitlements.
- Customer payment status.
- Transaction notifications.

Exit criteria:
- Tidak ada aktivasi dari redirect frontend saja.
- Duplicate webhook tidak menggandakan layanan.
- Amount/currency diverifikasi.

## Phase 4 — Guests and RSVP (minggu 11–12)
- Guest CRUD.
- CSV import/export.
- Grouping.
- Personal links and token revocation.
- RSVP.
- Guestbook/moderation jika masuk scope.
- Basic analytics.

Exit criteria:
- Token dan data tamu terlindungi.
- RSVP mematuhi party-size limit.
- CSV errors mudah dipahami.

Status: selesai — lihat `PHASE_4_REPORT.md` dan `adr/008-guests-and-rsvp.md`. Guestbook ditunda di luar scope fase ini.

## Phase 5 — Gallery and archive (minggu 13–14)
- Object storage adapter.
- Signed upload/download.
- Media validation and processing.
- Albums and previews.
- Quota enforcement.
- Archive jobs and temporary ZIP.
- Retention and cleanup.
- Storage reconciliation.

Exit criteria:
- Upload, download, quota, dan retention tests lulus.
- Job gagal terlihat dan dapat diulang.
- File tidak dapat diakses lintas wedding.

Status: selesai — lihat `PHASE_5_7_REPORT.md` dan `adr/009-media-storage-and-archive.md`. Penyimpangan utama: upload server-side (bukan presigned), pemrosesan sinkron, disk lokal, dan render PNG-only pada build ini.

## Phase 6 — Beta and launch hardening (minggu 15–16)
Status implementasi tambahan (November 2026): **Overhaul Beranda & Benchmark inviee.id Selesai (ADR 016)** — Interaktivitas mockup 3D, integrasi Google Fonts, pratinjau instan demo katalog, floating WhatsApp hotline, dan dashboard penyambutan pengantin telah selesai terpasang dan lolos pengujian otomatis.

- End-to-end tests.
- Security review.
- Performance checks.
- Backup/restore drill.
- Mobile/browser QA.
- Pilot pelanggan.
- Perbaikan bug.
- Support and incident playbook.

Exit criteria:
- Tidak ada blocker keamanan atau pembayaran.
- Backup restore teruji.
- Monitoring aktif.
- Proses support dan retensi jelas.

Status: sebagian besar selesai — end-to-end test, header keamanan, query budget, drill backup SQLite, dan playbook sudah ada (`PHASE_5_7_REPORT.md`, `BACKUP_RESTORE_DRILL.md`, `SUPPORT_PLAYBOOK.md`). QA mobile/browser dan pilot pelanggan belum dijalankan; error tracking eksternal dan alerting belum dipasang.

## Phase 7 — Growth based on evidence
- Guest photo contributions.
- WhatsApp Business integration.
- Custom domain.
- Partner portal.
- Referral.
- Advanced analytics.
- Additional templates.
- Multi-language.

Prioritaskan berdasarkan data pelanggan, margin, dan beban operasional.

Status: share link (WhatsApp/web share, tanpa pelacakan status) dan ringkasan RSVP selesai. Item lain ditunda dengan alasan dan sinyal bukti di `adr/010-beta-hardening-and-growth-scope.md` dan `PHASE_5_7_REPORT.md` bagian 3.

## Sprint protocol
Untuk setiap sprint:
1. Pilih scope kecil.
2. Tulis acceptance criteria.
3. Buat perubahan terkecil yang konsisten.
4. Jalankan tests.
5. Review security dan migration impact.
6. Demo hasil.
7. Update status dan dokumentasi.
