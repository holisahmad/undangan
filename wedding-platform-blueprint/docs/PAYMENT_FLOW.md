# Payment Flow

## 1. Tujuan

Membuat proses order dan aktivasi layanan konsisten, dapat diaudit, serta aman terhadap redirect palsu, webhook duplikat, dan transaksi yang statusnya belum jelas.

## 2. State model

Order:
- draft
- pending_payment
- paid
- partially_refunded (jika didukung)
- refunded
- cancelled
- payment_failed

Payment:
- initiated
- pending
- succeeded
- failed
- expired
- refunded

Status final dan transisi harus disesuaikan dengan provider yang dipilih. Jangan mengizinkan transisi arbitrer dari frontend.

## 3. Alur

1. Pelanggan memilih paket/add-on.
2. Server menghitung harga, diskon, pajak/biaya yang relevan, dan total.
3. Server membuat order snapshot.
4. Server membuat transaksi ke provider.
5. Pelanggan diarahkan ke checkout provider.
6. Provider mengirim webhook.
7. Backend memverifikasi signature dan detail transaksi.
8. Backend menyimpan event dengan provider event ID unik.
9. Backend memproses event secara idempotent.
10. Jika pembayaran terverifikasi, order diperbarui dan entitlement dibuat/diaktifkan.
11. Dashboard menampilkan status berdasarkan server.
12. Rekonsiliasi berkala memeriksa transaksi yang tertunda atau tidak konsisten.

## 4. Aturan keamanan

- Harga dihitung server-side.
- Jangan menerima total dari browser sebagai sumber kebenaran.
- Verifikasi currency dan amount.
- Verifikasi order reference.
- Validasi signature webhook.
- Idempotency untuk event duplikat.
- Simpan status event dan error code aman.
- Jangan mengaktifkan layanan dari redirect URL.
- Jangan simpan data kartu pembayaran.
- Gunakan sandbox sebelum produksi.

## 5. Entitlement

Entitlement harus mengacu pada item order dan layanan yang dibeli. Perpanjangan membuat perubahan masa layanan yang terdokumentasi; jangan menimpa histori transaksi lama.

Tentukan aturan untuk:
- Pembayaran terlambat.
- Order kedaluwarsa.
- Refund.
- Chargeback jika provider mendukung.
- Pembelian add-on setelah undangan aktif.
- Perpanjangan sebelum atau sesudah expiry.
- Duplikasi webhook.

## 6. Acceptance tests

- Webhook valid mengaktifkan layanan sekali.
- Webhook duplikat tidak menggandakan entitlement.
- Webhook invalid ditolak.
- Amount/currency mismatch tidak mengaktifkan layanan.
- Redirect sukses tanpa webhook valid tidak mengaktifkan layanan.
- Status pending tetap pending sampai terverifikasi.
- Refund mengikuti kebijakan dan tidak menghapus data secara tak terduga.
