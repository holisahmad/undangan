# API Specification — Initial Contract

> Kontrak ini bersifat konseptual. Sesuaikan prefix, format response, dan auth dengan stack yang dipilih. Jangan implementasikan semua endpoint sebelum fase terkait.

## 1. Konvensi

- Prefix contoh: `/api/v1`.
- JSON request/response.
- Validasi server-side.
- Error format konsisten: `code`, `message`, `details` yang aman.
- Semua endpoint privat memerlukan autentikasi dan otorisasi.
- Gunakan pagination pada list.
- Gunakan idempotency key untuk operasi yang dapat diulang dan webhook.
- Jangan mengembalikan data internal atau PII yang tidak diperlukan.

## 2. Authentication

- `POST /auth/register`
- `POST /auth/login`
- `POST /auth/logout`
- `POST /auth/forgot-password`
- `POST /auth/reset-password`
- `GET /me`

Gunakan mekanisme session/token yang sesuai frontend, deployment, dan threat model. Cookie session harus aman jika dipakai.

## 3. Templates

- `GET /templates`
- `GET /templates/{slug}`
- `GET /templates/{slug}/preview-data` jika diperlukan untuk preview publik yang aman

Admin:
- `POST /admin/templates`
- `PATCH /admin/templates/{id}`
- `POST /admin/templates/{id}/publish`
- `POST /admin/templates/{id}/archive`

## 4. Weddings / invitation editor

- `GET /weddings`
- `POST /weddings`
- `GET /weddings/{id}`
- `PATCH /weddings/{id}`
- `GET /weddings/{id}/draft`
- `PUT /weddings/{id}/draft`
- `POST /weddings/{id}/preview`
- `POST /weddings/{id}/publish`
- `POST /weddings/{id}/unpublish`
- `GET /weddings/{id}/publications`

Publish harus memvalidasi data wajib, template/version, entitlement, serta status pembayaran yang relevan.

## 5. Public invitation

- `GET /public/invitations/{slug}`
- `GET /public/invitations/{slug}/recipient/{token}` hanya jika token dan model akses disetujui
- `POST /public/invitations/{slug}/rsvp` pada fase guest management
- `POST /public/invitations/{slug}/guestbook` pada fase guest management

Endpoint publik harus membatasi field output, menerapkan rate limit, dan tidak mengungkap daftar tamu.

## 6. Orders / payments

- `POST /orders`
- `GET /orders/{id}`
- `POST /orders/{id}/checkout`
- `GET /orders/{id}/status`
- `POST /webhooks/payments/{provider}`

Webhook:
- Verifikasi signature sesuai provider.
- Simpan event ID unik.
- Idempotent terhadap event duplikat.
- Jangan mengaktifkan entitlement dari query parameter browser.
- Hindari mengembalikan detail internal pada response error.

## 7. Guests

- `GET /weddings/{id}/guests`
- `POST /weddings/{id}/guests`
- `PATCH /weddings/{id}/guests/{guestId}`
- `DELETE /weddings/{id}/guests/{guestId}`
- `POST /weddings/{id}/guests/import`
- `GET /weddings/{id}/guests/export`
- `POST /weddings/{id}/guests/{guestId}/rotate-link`
- `POST /weddings/{id}/guests/{guestId}/revoke-link`

CSV import harus membatasi ukuran, memvalidasi header dan baris, melaporkan error per baris, serta mencegah formula injection pada ekspor CSV.

## 8. Media

- `POST /weddings/{id}/media/upload-intent`
- `POST /weddings/{id}/media/{mediaId}/complete`
- `GET /weddings/{id}/media`
- `DELETE /weddings/{id}/media/{mediaId}`
- `POST /weddings/{id}/archives`
- `GET /weddings/{id}/archives/{jobId}`
- `POST /weddings/{id}/archives/{jobId}/download-link`

Upload intent harus memeriksa akses, kuota, batas ukuran, dan jenis media. Download link hanya diberikan kepada pihak berwenang dan memiliki masa berlaku singkat.

## 9. Admin

- `GET /admin/dashboard`
- `GET /admin/customers`
- `GET /admin/orders`
- `GET /admin/weddings`
- `PATCH /admin/weddings/{id}/lifecycle`
- `GET /admin/storage/usage`
- `GET /admin/jobs/failed`
- `GET /admin/audit-logs`

Setiap endpoint admin memerlukan permission spesifik dan mencatat tindakan sensitif.

## 10. Status HTTP

Gunakan status HTTP secara konsisten, termasuk:
- 200/201 untuk sukses.
- 202 untuk proses asinkron yang diterima.
- 400 untuk request tidak valid.
- 401 untuk belum autentikasi.
- 403 untuk tidak berhak.
- 404 untuk resource tidak tersedia/agar tidak membocorkan keberadaan resource sesuai kebijakan.
- 409 untuk konflik state/idempotency.
- 422 untuk validasi field bila sesuai konvensi framework.
- 429 untuk rate limit.

## 11. Pagination dan filtering

Gunakan parameter konsisten, misalnya `page`, `per_page`, `sort`, dan filter yang diizinkan. Batasi `per_page`. Jangan menerima kolom sort arbitrary dari pengguna tanpa whitelist.

## 12. API acceptance criteria

- Kontrak API terdokumentasi dan sesuai implementasi.
- Endpoint privat memiliki test autentikasi dan otorisasi.
- Endpoint publik memiliki rate limit.
- Error tidak membocorkan stack trace, secret, atau data privat.
- Webhook dan operasi publish aman terhadap retry.
