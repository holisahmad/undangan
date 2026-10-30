# Testing Strategy

## 1. Piramida pengujian

- Unit tests untuk business rules dan state transitions.
- Integration tests untuk database, storage adapter, queue, dan payment adapter.
- Feature/API tests untuk autentikasi, otorisasi, validasi, dan response.
- End-to-end tests untuk alur penting pelanggan.
- Security tests untuk tenant isolation, upload, webhook, dan token.
- Manual visual QA untuk template dan responsive layouts.

## 2. Prioritas test

### Identity/access
- Register/login/reset.
- Unauthorized access.
- User A tidak dapat mengakses wedding milik User B.
- Role admin terbatas.

### Invitation
- Draft save.
- Publish validation.
- Publish version snapshot.
- Unpublish/expire.
- Template version compatibility.
- Preview konsisten dengan publication.

### Payment
- Valid webhook.
- Invalid signature.
- Duplicate event.
- Amount mismatch.
- Redirect palsu.
- Retry dan status pending.

### Guest/RSVP
- Token valid/revoked/expired.
- RSVP party size tidak melebihi batas.
- Rate limit.
- Data guest tidak bocor.

### Media/archive
- Upload valid/invalid.
- Quota race condition.
- File ownership.
- Signed URL expiry.
- ZIP isolation.
- Job retry.
- Retention and deletion.
- Storage reconciliation.

### UI
- Empty/loading/error/success states.
- Mobile layout.
- Long names/address.
- Reduced motion.
- Keyboard/focus.
- Contrast.

## 3. Quality gates

Sebelum merge:
- Formatter/linter lulus.
- Static analysis/type-check lulus.
- Test yang relevan lulus.
- Build lulus.
- Migrasi diuji.
- Security-sensitive changes ditinjau.
- Dokumentasi diperbarui.

Jangan menonaktifkan test atau mengubah assertion agar build tampak hijau tanpa memahami alasan.

## 4. Test data

- Gunakan data sintetis.
- Jangan memasukkan data pribadi pelanggan ke fixture.
- Test payment provider menggunakan sandbox/mock.
- Test storage menggunakan local emulator/mock yang sesuai.
- Bersihkan data test secara aman.

## 5. Release checks

- Backup berhasil.
- Restore diuji sesuai jadwal.
- Environment variables tersedia.
- Webhook endpoint production diverifikasi.
- Queue worker berjalan.
- Monitoring dan alert aktif.
- Kebijakan retensi cocok dengan scheduled jobs.
- Rollback plan tersedia.
