# Deployment & Operations

## 1. Environments

Pisahkan:
- Local development.
- Test/CI.
- Staging.
- Production.

Jangan menggunakan database atau bucket production untuk test lokal.

## 2. Configuration

Environment variables/secrets minimal mencakup:
- Database credentials.
- Session/auth secrets.
- Object storage credentials.
- Payment provider keys.
- Webhook secrets.
- Email provider keys.
- Queue/cache config.
- Application URL.
- Logging/error tracking keys.

Gunakan secret manager atau environment deployment. Jangan commit `.env` atau credential.

## 3. Deploy process

1. CI menjalankan lint, type-check, tests, dan build.
2. Review migrasi dan dampak backward compatibility.
3. Backup sebelum perubahan berisiko.
4. Deploy aplikasi.
5. Jalankan migrasi secara terkontrol.
6. Pastikan worker/cron diperbarui.
7. Jalankan smoke tests.
8. Periksa logs, errors, dan job queue.
9. Sediakan rollback plan.

Untuk migrasi besar, gunakan strategi expand-and-contract agar versi aplikasi lama dan baru tidak saling merusak selama deployment.

## 4. Scheduled jobs

- Notifikasi masa aktif.
- Expiry/lifecycle.
- Retention/deletion.
- Storage reconciliation.
- Payment reconciliation.
- Cleanup archive ZIP.
- Failed job alerts.

Job harus idempotent dan memiliki observability.

## 5. Backup and recovery

- Backup PostgreSQL terjadwal.
- Tentukan RPO/RTO berdasarkan kebutuhan bisnis.
- Enkripsi backup dan batasi akses.
- Tentukan apakah object storage memerlukan versioning/replication.
- Uji restore secara berkala.
- Dokumentasikan prosedur pemulihan.
- Periksa interaksi restore dengan data yang telah dihapus sesuai retensi.

## 6. Observability

Pantau:
- Error rate.
- Response time.
- Queue depth.
- Failed jobs.
- Payment webhook failures.
- Upload/processing failures.
- Storage bytes dan egress.
- Database capacity.
- Scheduled job lag.
- Expiry/retention failures.

Log tidak boleh berisi password, token, signed URL, atau PII yang tidak diperlukan.

## 7. Launch checklist

- Domain dan TLS.
- DNS.
- Production environment.
- Payment provider live credentials dan webhook.
- Email sender configuration.
- Object storage policies.
- Backup/restore verified.
- Admin MFA bila tersedia.
- Privacy/terms pages.
- Error tracking dan alerts.
- Rate limiting.
- Smoke test mobile.
- Support process.
- Rollback plan.
