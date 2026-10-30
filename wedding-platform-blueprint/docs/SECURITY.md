# Security Requirements

## 1. Prinsip

- Least privilege.
- Defense in depth.
- Server-side validation.
- Authorization per resource.
- Minimize stored personal data.
- Secrets outside repository.
- Log securely.
- Test security regressions.

## 2. Authentication

- Gunakan library/framework auth yang terawat.
- Password di-hash menggunakan mekanisme modern bawaan framework.
- Rate-limit login, reset password, dan endpoint publik sensitif.
- Token reset memiliki masa berlaku dan hanya dapat digunakan sekali.
- Cookie session, jika digunakan, memiliki flag Secure, HttpOnly, dan SameSite yang sesuai.
- Lindungi CSRF sesuai arsitektur.
- Sediakan logout dan invalidasi sesi yang benar.

## 3. Authorization / tenant isolation

- Setiap request memverifikasi akses terhadap wedding, guest, media, order, dan archive.
- Jangan percaya ID dari frontend.
- UUID tidak menggantikan authorization.
- Uji IDOR/BOLA dengan dua akun berbeda.
- Admin permissions spesifik dan dibatasi.
- Support tidak otomatis memiliki akses ke media privat.

## 4. Input and output

- Validasi input di server.
- Escape output sesuai konteks.
- Cegah XSS pada guestbook dan teks undangan.
- Validasi URL eksternal dan batasi risiko SSRF bila server mengambil URL.
- Batasi panjang teks, jumlah item, dan ukuran payload.
- CSV export harus mencegah formula injection.
- Query database harus menggunakan parameter binding/ORM aman.

## 5. Upload safety

- Batas ukuran dan kuota sebelum upload.
- Validasi ekstensi, MIME yang terdeteksi, dan file signature.
- Jangan mempercayai filename atau Content-Type browser.
- Gunakan object key acak; jangan jadikan filename pengguna sebagai object key.
- Simpan file pending di area non-public.
- Scan/proses media dalam lingkungan yang dibatasi bila tersedia.
- Tolak format yang tidak dibutuhkan.
- Batasi dimensi/resolusi untuk pemrosesan gambar.
- Cegah path traversal dan decompression bombs jika menerima archive.
- Jangan menjalankan file unggahan sebagai kode.
- Rate-limit upload publik.

## 6. Signed URLs

- Berumur pendek.
- Diterbitkan hanya setelah otorisasi.
- Tidak dicatat ke log.
- Jangan disimpan sebagai URL permanen.
- Cabut akses dengan memblokir pembuatan link baru dan, jika provider mendukung, kebijakan akses terkait.

## 7. Payments and webhooks

- Verifikasi signature dan event provider.
- Terapkan idempotency.
- Simpan event ID unik.
- Validasi amount, currency, order reference, dan status.
- Jangan percaya query parameter browser.
- Hindari replay event.
- Jangan menyimpan data kartu sensitif.

## 8. Admin and audit

- Terapkan MFA untuk admin bila didukung.
- Catat actor, action, resource, result, timestamp, request ID.
- Redact PII dan secret dari log.
- Tindakan destruktif memerlukan konfirmasi dan permission khusus.
- Jangan membuat support impersonation tanpa kontrol dan audit.

## 9. Rate limiting and abuse

Terapkan batas untuk:
- Login/reset.
- RSVP dan guestbook.
- Recipient token lookup.
- Upload.
- Archive generation.
- Public page endpoints jika perlu.
- Contact forms.

Tambahkan batas kuota per acara dan deteksi lonjakan penggunaan.

## 10. Data protection

- TLS untuk transit.
- Enkripsi storage/provider bila tersedia.
- Backup terlindungi.
- Akses production dibatasi.
- Retensi dan penghapusan terdokumentasi.
- Prosedur insiden dan notifikasi mengikuti kewajiban yang berlaku.

## 11. Security test checklist

- IDOR/BOLA antar pelanggan.
- Privilege escalation.
- XSS pada teks undangan/ucapan.
- CSRF jika relevan.
- File upload spoofing.
- Signed URL expiry.
- Webhook replay.
- Race condition kuota.
- Archive download authorization.
- Secret leakage.
- Rate limit bypass.
