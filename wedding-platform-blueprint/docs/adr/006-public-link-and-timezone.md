# ADR 006: Tautan Publik dan Penanganan Timezone Acara

- Status: Accepted
- Date: 2026-10-30

## Context

`API_SPEC.md` mencantumkan `GET /public/invitations/{slug}` dan `DATABASE_SCHEMA.md` membuka pertanyaan timezone acara serta perubahan slug setelah publikasi. Aplikasi fase 1 adalah web server-rendered, bukan API JSON.

## Decision

**Tautan publik.** Halaman publik memakai `GET /u/{slug}` (name: `invitations.public`). Slug dibuat dari judul internal + 6 karakter acak, unik di database, dan tidak diedit dari UI. Judul internal bebas berubah kapan saja.

**Slug setelah publikasi.** Selama fase 1 slug bersifat tetap. Mengubah slug setelah tautan dibagikan akan memutus tautan dan belum ada kebijakan redirect; perubahan tersebut ditunda sampai ada kebutuhan produk.

**Timezone.** Waktu acara disimpan dalam UTC (`starts_at`, `ends_at`). Kolom `timezone` menyimpan zona acara (default `Asia/Jakarta`). Input `datetime-local` ditafsirkan dalam zona acara lalu dikonversi ke UTC sebelum disimpan. Saat publikasi, snapshot menyimpan ISO-8601 UTC **dan** label siap tampil (`starts_label`, `ends_label`) yang dihitung pada zona acara. Halaman publik hanya memakai label tersebut sehingga tidak bergantung pada timezone perangkat tamu.

**Keamanan output.** Halaman publik hanya membaca `published_snapshot` melalui DTO `PublicInvitation` yang memutihkan field. Judul internal, ID internal, dan data relasi live tidak pernah dikirim. Halaman memakai `noindex` dan rate limit per IP.

## Consequences

Positive:
- Tautan pendek dan stabil.
- Tidak ada konversi waktu di sisi klien; perilaku konsisten di semua perangkat.
- Tidak ada kebocoran field internal ke halaman publik.

Trade-offs:
- Label waktu dibekukan saat publikasi; perubahan timezone acara memerlukan publish ulang agar tampil.
- Perubahan slug/redirect belum didukung.
- Tamu di zona waktu berbeda melihat waktu acara sesuai zona acara, bukan zona lokal mereka.

## Revisit when

- Dibutuhkan tautan personal per tamu (token) atau penyesuaian waktu ke zona tamu.
- Dibutuhkan perubahan slug dengan redirect permanen.
