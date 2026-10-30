# Wedding Experience Platform — AI Agent Project Blueprint

> Dokumen induk untuk membangun platform undangan pernikahan digital yang dinamis, elegan, aman, dan dapat dikembangkan bertahap.

## Tujuan

Membangun platform web multi-pelanggan untuk:
- Membuat dan menerbitkan undangan pernikahan dari template dinamis.
- Mengelola informasi acara, penerima, tautan personal, RSVP, dan ucapan.
- Mengelola pembayaran, paket, masa aktif, serta perpanjangan.
- Menyediakan galeri foto dan arsip dengan masa akses dan retensi yang transparan.
- Menyediakan dashboard pelanggan dan panel admin dengan kontrol akses yang benar.
- Mendukung pertumbuhan melalui SEO, referral, serta kemitraan wedding organizer dan fotografer.

## Cara AI Agent menggunakan dokumen

1. Baca seluruh `docs/AI_AGENT_RULES.md`.
2. Baca `docs/PRD.md`, `docs/ARCHITECTURE.md`, `docs/DATABASE_SCHEMA.md`, dan dokumen terkait fase yang akan dikerjakan.
3. Audit repository yang sudah ada sebelum mengubah kode. Jangan mengasumsikan proyek kosong.
4. Buat rencana implementasi kecil dengan daftar file, migrasi, risiko, dan test.
5. Implementasikan satu fase/tiket dalam satu waktu.
6. Jalankan lint, type-check, test, dan build yang relevan.
7. Laporkan hasil aktual, perintah yang dijalankan, test yang lulus/gagal, serta pekerjaan tersisa. Jangan mengklaim test lulus jika tidak dijalankan.

## Dokumen

- `docs/PRD.md` — kebutuhan produk dan ruang lingkup.
- `docs/BUSINESS_MODEL.md` — paket, pendapatan, dan unit economics.
- `docs/ARCHITECTURE.md` — arsitektur aplikasi dan keputusan teknologi.
- `docs/DATABASE_SCHEMA.md` — entitas, relasi, dan aturan data.
- `docs/API_SPEC.md` — kontrak API awal.
- `docs/DESIGN_SYSTEM.md` — visual language dan komponen UI.
- `docs/UI_UX_FLOWS.md` — alur pelanggan, tamu, dan admin.
- `docs/SECURITY.md` — keamanan dan otorisasi.
- `docs/PRIVACY_AND_RETENTION.md` — privasi, masa aktif, retensi, dan penghapusan.
- `docs/PAYMENT_FLOW.md` — pesanan dan pembayaran.
- `docs/MEDIA_STORAGE.md` — upload, galeri, unduh, dan arsip.
- `docs/TESTING.md` — strategi dan kriteria pengujian.
- `docs/DEPLOYMENT.md` — deployment, backup, dan observability.
- `docs/ROADMAP.md` — fase dan acceptance criteria.
- `docs/AI_AGENT_RULES.md` — aturan kerja agent.
- `docs/adr/001-modular-monolith.md` — keputusan modular monolith.
- `docs/adr/002-database-choice.md` — keputusan database.
- `docs/adr/003-media-storage.md` — keputusan object storage.

## Default yang belum final

- Target awal: pelanggan langsung (B2C), dengan desain data yang tidak menghalangi mitra B2B di fase lanjutan.
- Bahasa antarmuka awal: Bahasa Indonesia.
- Zona waktu: simpan timestamp sistem secara konsisten (UTC bila tipe dan infrastruktur mendukung), tampilkan jadwal acara sesuai zona waktu acara.
- Masa aktif undangan default untuk validasi produk: 12 bulan sejak publikasi; harus configurable dan dapat diubah melalui konfigurasi bisnis.
- Masa akses galeri dan retensi arsip adalah kebijakan terpisah dari masa aktif undangan.
- Paket/harga contoh di dokumen bisnis hanyalah asumsi untuk diuji, bukan harga final.
- Pilihan stack final harus dikonfirmasi setelah audit repository dan kemampuan tim. Jangan membuat ulang proyek yang sudah berjalan tanpa alasan terukur.

## Definition of Done umum

Sebuah fitur dianggap selesai jika:
- Acceptance criteria fitur terpenuhi.
- Validasi server-side dan otorisasi diterapkan.
- Test relevan ditambahkan dan dijalankan.
- Migrasi dapat diterapkan dan, jika sesuai, rollback-nya diuji.
- UI memiliki loading, empty, error, success, dan state responsif yang diperlukan.
- Tidak ada secret di repository.
- Dokumentasi yang terpengaruh diperbarui.
- Tidak ada klaim hasil test yang tidak benar.
