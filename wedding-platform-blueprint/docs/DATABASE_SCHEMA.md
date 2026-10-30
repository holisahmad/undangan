# Database Schema — Conceptual Design

> Ini adalah model konseptual. Agent harus memvalidasi relasi, tipe data, indeks, dan migrasi terhadap stack serta fitur yang benar-benar masuk fase.

## 1. Prinsip data

- Relasi bisnis utama menggunakan tabel relasional dan foreign key.
- JSONB hanya untuk konfigurasi yang memang fleksibel dan tervalidasi.
- Uang disimpan sebagai NUMERIC/decimal dalam satuan mata uang yang jelas; jangan gunakan floating-point.
- Timestamp sistem konsisten; jadwal acara menyimpan timezone IANA bila perlu.
- Soft delete hanya jika memiliki kebutuhan dan kebijakan yang jelas; jangan menjadikannya pengganti retensi.
- Gunakan unique constraint dan idempotency key pada proses eksternal.
- Indeks dibuat berdasarkan query nyata.
- PII diminimalkan dan dibatasi aksesnya.

## 2. Identity

### users
- id (PK)
- name
- email (unique, normalized)
- password_hash atau referensi identity provider
- status
- email_verified_at
- created_at, updated_at

### user_profiles
- user_id (PK/FK users)
- display_name
- locale
- timezone
- optional contact data

### roles / user_roles
- roles: id, name, description
- user_roles: user_id, role_id, scope_type, scope_id (sesuaikan model otorisasi)

Jangan menyimpan password plaintext. Gunakan mekanisme auth yang matang dari framework.

## 3. Wedding dan acara

### weddings
- id (PK)
- owner_user_id (FK users)
- slug (unique sesuai strategi)
- internal_title
- lifecycle_status
- created_at, updated_at

### wedding_members
- wedding_id (FK)
- user_id (FK)
- role
- unique(wedding_id, user_id)

### wedding_partners
- id (PK)
- wedding_id (FK)
- partner_type
- display_name
- optional biography
- display_order

### wedding_events
- id (PK)
- wedding_id (FK)
- event_type
- title
- starts_at, ends_at
- timezone
- venue_name
- address
- latitude/longitude optional
- map_url
- display_order

Satu wedding dapat memiliki banyak rangkaian acara. Validasi koordinat dan URL di server.

## 4. Template dan publikasi

### invitation_templates
- id (PK)
- slug (unique)
- name
- category
- status
- thumbnail_object_key
- feature_schema
- created_at, updated_at

### template_versions
- id (PK)
- template_id (FK)
- version
- renderer_key atau komponen yang terdaftar
- config_schema_version
- status
- created_at
- unique(template_id, version)

Jangan menjalankan kode arbitrer yang diunggah melalui konfigurasi template. Template renderer harus berasal dari kode yang disetujui.

### invitation_drafts
- id (PK)
- wedding_id (FK, unique bila satu draft aktif per wedding)
- template_version_id (FK)
- content_json/config_json yang tervalidasi
- updated_at

### invitation_publications
- id (PK)
- wedding_id (FK)
- version_number
- published_snapshot
- published_by (FK users)
- published_at
- unpublished_at nullable
- unique(wedding_id, version_number)

Snapshot harus memiliki format/version yang dapat dibaca renderer. Tentukan apakah snapshot menyimpan object key media atau referensi media yang aman.

### service_entitlements
- id (PK)
- wedding_id (FK)
- order_item_id nullable
- entitlement_type (invitation, gallery, archive, storage, feature)
- starts_at
- expires_at nullable sesuai jenis layanan
- limits_json yang tervalidasi
- status
- created_at, updated_at

Entitlement adalah hak layanan yang diberikan kepada pelanggan. Kebijakan expiry harus memeriksa entitlement yang tepat, bukan hanya satu tanggal global.

## 5. Produk dan pembayaran

### products
- id, code unique, name, type, status

### plans
- id, code unique, name, price_amount, currency, status

### plan_features
- id, plan_id FK, feature_key, value_json atau typed limit
- unique(plan_id, feature_key)

### orders
- id (PK)
- public_order_number (unique)
- user_id (FK)
- status
- currency
- subtotal_amount
- discount_amount
- total_amount
- created_at, updated_at

### order_items
- id (PK)
- order_id (FK)
- product_id/plan_id FK
- quantity
- unit_price_amount
- total_amount
- configuration_snapshot

### payments
- id (PK)
- order_id (FK)
- provider
- provider_transaction_id nullable
- status
- amount
- currency
- idempotency_key
- paid_at nullable
- created_at, updated_at

### payment_events
- id (PK)
- provider
- provider_event_id (unique per provider)
- event_type
- received_at
- processing_status
- safe_payload_reference atau payload teredaksi
- processed_at nullable

Jangan simpan data kartu pembayaran sensitif. Hindari menyimpan payload webhook penuh jika mengandung data yang tidak dibutuhkan.

## 6. Guest management (fase lanjutan)

### guests
- id (PK)
- wedding_id (FK)
- display_name
- contact_phone nullable, dibatasi akses
- group_id nullable
- allowed_party_size
- created_at, updated_at

### guest_groups
- id (PK)
- wedding_id (FK)
- name

### guest_invitation_links
- id (PK)
- guest_id (FK)
- token_hash (unique)
- expires_at nullable
- revoked_at nullable
- created_at

Simpan hash token bila token bersifat bearer secret. Token mentah hanya ditampilkan saat dibuat dan jangan dicatat ke log.

### rsvps
- id (PK)
- guest_id (FK)
- status
- party_size
- message nullable
- submitted_at
- updated_at

Tentukan apakah RSVP terbaru mengganti respons sebelumnya atau disimpan sebagai riwayat; pilih satu aturan dan dokumentasikan.

### guestbook_entries
- id (PK)
- wedding_id (FK)
- guest_id nullable
- display_name
- message
- moderation_status
- created_at
- moderated_at nullable

## 7. Media dan arsip

### media_assets
- id (PK)
- wedding_id (FK)
- uploaded_by (FK users nullable untuk upload publik yang diizinkan)
- object_key (unique)
- original_filename (sanitized)
- detected_mime_type
- size_bytes
- checksum
- media_kind
- processing_status
- moderation_status
- created_at, deleted_at nullable

### albums
- id (PK)
- wedding_id (FK)
- name
- visibility
- created_at

### album_media
- album_id (FK)
- media_asset_id (FK)
- display_order
- unique(album_id, media_asset_id)

### archive_jobs
- id (PK)
- wedding_id (FK)
- requested_by (FK users)
- status
- output_object_key nullable
- expires_at nullable
- error_code nullable
- created_at, completed_at nullable

### storage_usage
- wedding_id (PK/FK)
- original_bytes
- preview_bytes
- archive_bytes
- last_reconciled_at

Jangan mengandalkan counter saja untuk mencegah over-quota: reservasi kuota saat upload dan rekonsiliasi dengan object storage.

## 8. Notifikasi, audit, dan operasional

### notifications
- id, user_id, type, payload minimal, status, created_at, read_at nullable

### notification_deliveries
- id, notification_id, channel, provider_message_id nullable, status, attempt_count, last_error_code nullable

### audit_logs
- id
- actor_user_id nullable
- action
- resource_type
- resource_id
- result
- request_id
- metadata yang telah disanitasi
- created_at

Jangan menulis token, password, signed URL, atau data sensitif penuh ke log.

### background job storage
Gunakan queue bawaan stack jika memadai. Tabel job khusus bukan keharusan. Pastikan failed job dapat diperiksa dan diulang secara aman.

## 9. Indeks dan constraint awal

Evaluasi indeks untuk:
- users(email)
- weddings(owner_user_id, lifecycle_status)
- wedding_events(wedding_id, starts_at)
- invitation_templates(status, category)
- invitation_publications(wedding_id, version_number)
- orders(user_id, created_at)
- orders(status, created_at)
- payments(provider, provider_transaction_id)
- guests(wedding_id, group_id)
- rsvps(guest_id, submitted_at)
- media_assets(wedding_id, processing_status)
- media_assets(object_key) unique
- service_entitlements(wedding_id, entitlement_type, status)

Tambahkan constraint status dan nilai numerik yang valid. Gunakan partial index bila sesuai dengan database dan pola query.

## 10. Keputusan yang harus dituntaskan sebelum migrasi

- Apakah satu wedding dapat memiliki banyak pemilik/kolaborator?
- Apakah slug dapat diubah setelah publikasi?
- Bagaimana menyimpan versi konten dan template?
- Bagaimana menangani timezone acara?
- Apakah RSVP mengizinkan banyak peserta?
- Bagaimana entitlement terhubung ke item pesanan?
- Apa kebijakan penghapusan media dan backup?
- Apakah tenant/mitra B2B masuk fase awal atau hanya fase lanjutan?
