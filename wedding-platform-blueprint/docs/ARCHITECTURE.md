# Architecture

## 1. Prinsip

- Mulai dengan modular monolith.
- Pisahkan domain bisnis melalui modul, bukan deployable services.
- Gunakan API/service layer yang konsisten.
- Database relational sebagai sumber kebenaran untuk status bisnis.
- Object storage untuk media, bukan BLOB database.
- Background jobs untuk thumbnail, arsip ZIP, notifikasi, retensi, dan rekonsiliasi.
- Semua operasi sensitif memiliki otorisasi server-side.
- Jangan menambahkan teknologi tanpa kebutuhan terukur.

## 2. Stack kandidat

Pilihan default bila tim nyaman dengan Laravel:
- Frontend: Next.js + TypeScript.
- Backend: Laravel API.
- Database: PostgreSQL.
- Media: object storage kompatibel S3.
- Queue/cache: Redis bila diperlukan.
- Worker: Laravel queue worker.
- Payment: provider yang dipilih setelah evaluasi dokumentasi dan biaya.
- Email transaksional: provider yang dipilih setelah evaluasi.
- Monitoring: error tracking + log terstruktur.

Alternatif: stack TypeScript end-to-end jika tim lebih kompeten di sana. Sebelum coding, audit repository dan tulis ADR final. Jangan menjalankan dua backend dengan tanggung jawab tumpang tindih.

## 3. Batas modul

- Identity & Access
- Customer Workspace
- Wedding/Event
- Template & Rendering
- Publication & Lifecycle
- Guest & RSVP
- Orders & Payments
- Media & Storage
- Archive & Retention
- Notifications
- Admin Operations
- Audit & Observability

Modul berkomunikasi melalui service/application layer yang jelas. Hindari akses database lintas modul yang tidak terkontrol.

## 4. Diagram logis

```text
Browser
  |
  v
Frontend (marketing, invitation, customer dashboard, admin)
  |
  v
Backend/API
  +-- Identity & Access
  +-- Wedding & Events
  +-- Template & Publication
  +-- Guest & RSVP
  +-- Orders & Payments
  +-- Media & Archive
  +-- Admin & Audit
  |
  +--> PostgreSQL
  +--> Object Storage
  +--> Queue --> Workers
  +--> Payment Provider
  +--> Email/Notification Provider
```

## 5. Rendering undangan

- Invitation data tidak boleh disimpan sebagai salinan template yang tak terstruktur.
- Template memiliki ID, slug, status, versi, schema konfigurasi, dan daftar fitur.
- Konfigurasi divalidasi dengan schema di server.
- Publikasi menghasilkan snapshot/version yang konsisten.
- Perubahan template tidak boleh merusak publikasi lama tanpa migrasi terencana.
- Rendering publik hanya mengambil field yang memang diizinkan untuk publik.
- Gunakan cache dengan invalidasi saat publish/unpublish; jangan cache halaman private sebagai public.

## 6. Multi-tenancy dan akses

- Setiap resource milik workspace/acara yang jelas.
- Verifikasi hak akses di setiap operasi read/write.
- Gunakan policy/authorization service.
- UUID bukan pengganti otorisasi.
- Admin role dibatasi dengan least privilege.
- Gunakan audit log untuk tindakan sensitif.

## 7. Proses asinkron

Gunakan queue untuk:
- Thumbnail dan optimasi media.
- Pembuatan ZIP.
- Notifikasi kedaluwarsa.
- Pengiriman email.
- Retensi dan penghapusan.
- Rekonsiliasi storage.
- Pemrosesan webhook yang membutuhkan kerja lanjutan.

Job harus idempotent bila mungkin, memiliki retry/backoff, timeout, dan dead-letter/failure visibility.

## 8. Pembayaran

- Browser tidak menentukan bahwa pembayaran berhasil.
- Validasi signature webhook dan status transaksi melalui mekanisme provider.
- Simpan event eksternal dengan idempotency key/provider event ID yang unik.
- Rekonsiliasi transaksi secara berkala.
- Entitlement layanan diaktifkan melalui aturan server-side yang eksplisit.

## 9. Media

- Upload menggunakan signed URL bila didukung.
- Server memvalidasi entitlement, kuota, ukuran, MIME/signature, dan kepemilikan.
- Jangan percaya Content-Type dari browser saja.
- File yang belum divalidasi tidak langsung ditampilkan publik.
- Original, preview, dan arsip dibedakan.
- Signed download URL berumur pendek.
- Jangan menyimpan URL signed sebagai lokasi permanen di database; simpan object key.

## 10. Lingkungan

- Local, test/staging, production terpisah.
- Konfigurasi melalui environment/secrets manager.
- Tidak ada secret di source control.
- Migrasi database dijalankan secara terkontrol.
- Backup dan restore diuji sebelum launch.
- Gunakan deployment rollback yang sesuai kemampuan stack.

## 11. Keputusan sebelum implementasi

Agent harus menulis ADR untuk:
- Backend final.
- Auth/session strategy.
- Payment provider.
- Object storage provider.
- Email provider.
- Queue strategy.
- Hosting/deployment.
- Kebijakan cache dan CDN.
