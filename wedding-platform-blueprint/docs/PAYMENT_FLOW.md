# Payment Flow

## 1. Tujuan

Membuat proses order dan aktivasi layanan konsisten, dapat diaudit, serta aman terhadap bukti transfer palsu, verifikasi ganda, dan nominal yang tertukar antar pesanan.

## 2. Keputusan: transfer bank manual

Versi awal dokumen ini merancang checkout melalui payment gateway dengan webhook bertanda tangan. **Implementasi sekarang memakai transfer bank manual sebagai alur default**, karena itu yang diminta pemilik produk:

- Pelanggan tidak diarahkan ke halaman provider mana pun.
- Sistem menampilkan satu atau lebih rekening tujuan (tidak dibatasi satu bank).
- Setiap pesanan mendapat **kode unik 3 digit** yang ditambahkan ke harga paket.
- Setiap kode hanya berlaku selama **batas waktu pembayaran** (default 24 jam).
- Pelanggan mengunggah bukti transfer; uang dianggap masuk hanya setelah operator memverifikasi mutasi rekening.

Gateway `fake` tetap ada untuk pengujian mekanika webhook dan demo lokal, dan tidak dipakai di produksi (`PAYMENT_FAKE_ENABLED=false`). Menambah provider sungguhan nanti tidak mengubah kontrak order: cukup implementasi `App\Contracts\Payments\PaymentGateway` dan daftarkan di `config/platform.php`.

Keputusan ini menggantikan asumsi gateway pada `docs/BUSINESS_MODEL.md` dan `docs/ROADMAP.md`, tidak mengubah model status order, dan dicatat sebagai keputusan resmi di `adr/011-manual-bank-transfer-payments.md`.

## 3. State model

Order (`App\Enums\OrderStatus`):

- `pending_payment`
- `paid`
- `payment_failed`
- `cancelled`
- `refunded`

Payment (`App\Enums\PaymentStatus`): `initiated`, `pending`, `succeeded`, `failed`, `expired`, `refunded`.

Konfirmasi transfer (`App\Enums\PaymentConfirmationStatus`): `submitted`, `verified`, `rejected`.

Batas waktu pembayaran **tidak** menambah status order baru. Order yang lewat tenggat tetap `pending_payment`; label tampilan berubah menjadi "Kedaluwarsa" lewat `Order::displayStatusLabel()`, dan percobaan pembayarannya yang ditandai `expired`. Dengan begitu pelanggan dapat membuka kembali pesanan yang sama dengan kode baru tanpa membuat order duplikat.

## 4. Nominal dan kode unik

- Harga tetap berasal dari katalog (`plans.price_amount`), bukan dari browser.
- `orders.unique_code` berisi bilangan 100–999 yang dibulatkan 3 digit saat ditampilkan.
- Total transfer = `orders.total_amount + orders.unique_code` (`Order::amountDue()`).
- Kode hanya unik di antara order berstatus `pending_payment` yang jendelanya masih terbuka (`App\Support\Payments\PaymentWindow`), supaya satu baris mutasi bank hanya bisa cocok ke satu pesanan.
- Nominal yang dikirim pelanggan harus **tepat**, termasuk tiga digit terakhir. Validasi dilakukan dua kali: di form (`Rule::in`) dan lagi di sisi action sebelum konfirmasi disimpan.

Kode dan tenggat dibuat saat order dibuat (`CreateOrder`), dan dapat dibuat ulang lewat `orders.payment.regenerate` bila jendela sudah tertutup.

## 5. Batas waktu

- `orders.payment_expires_at` diisi `now() + PAYMENT_WINDOW_HOURS` (default 24 jam).
- Selama jendela terbuka: pelanggan dapat melihat instruksi dan mengunggah bukti.
- Setelah jendela tertutup:
  - halaman instruksi menampilkan status kedaluwarsa dan tombol "Buat kode pembayaran baru";
  - unggahan bukti ditolak (`PaymentWindowClosed`);
  - command `payments:expire` (dijadwalkan tiap 5 menit) menandai payment `initiated`/`pending` menjadi `expired` dan mencatat audit `payment.window_expired`.
- Konfirmasi yang sudah berstatus `submitted` tidak pernah dikedaluwarsakan otomatis; operator tetap menyelesaikan antrean verifikasi.

## 6. Alur pelanggan

1. Pelanggan memilih paket dan membuat order.
2. Server menghitung total, membuat nomor pesanan, kode unik 3 digit, dan batas waktu.
3. `POST orders/{order}/checkout` membuat satu percobaan pembayaran (`idempotency_key = order:{id}`, provider `bank_transfer`) lalu mengarahkan ke `checkout/transfer/{order}`.
4. Halaman instruksi menampilkan total transfer, kode unik, hitung mundur, daftar rekening (dengan tombol salin), dan langkah pembayaran.
5. Pelanggan mentransfer, lalu mengisi form konfirmasi: nama pengirim, bank pengirim, tanggal transfer, nominal, catatan opsional, dan berkas bukti (JPG/PNG/PDF).
6. `SubmitPaymentConfirmation` memeriksa jendela, nominal, status order, dan duplikasi, lalu menyimpan baris `payment_confirmations` berstatus `submitted`.
7. Order **tetap** `pending_payment`. Halaman pesanan menampilkan "menunggu verifikasi".
8. Operator memverifikasi atau menolak dari console admin.
9. Setelah diverifikasi, masa aktif undangan bertambah dan pemilik menerima email.

## 7. Verifikasi operator

- `admin/orders` menampilkan antrean bukti transfer (`stats.review_count`) dan penanda "Perlu verifikasi" per baris.
- `admin/orders/{order}` menampilkan data pengirim, tanggal, nominal, catatan, dan tautan unduh bukti.
- `ReviewPaymentConfirmation::verify()`:
  - hanya menerima konfirmasi berstatus `submitted`;
  - menolak bila nominal konfirmasi berbeda dari `amountDue()` (mis. baris diubah di luar aplikasi);
  - memanggil `MarkOrderPaid` — satu-satunya tempat order menjadi lunas.
- `ReviewPaymentConfirmation::reject()` mewajibkan catatan alasan, tidak menutup jendela, dan mengizinkan pelanggan mengunggah bukti baru.
- Setelah order lunas, tombol verifikasi berikutnya mengembalikan 404: tidak ada entitlement ganda.

## 8. Settlement tunggal (`MarkOrderPaid`)

Baik verifikasi manual maupun webhook provider (sandbox) berakhir di action yang sama:

- transaksi database + `lockForUpdate()` pada order;
- bila order sudah lunas: audit `payment.duplicate`, return `newlyPaid = false`;
- upsert `payments` berdasarkan `idempotency_key`, set `succeeded`, `amount`, `currency`, `paid_at`;
- order menjadi `paid`, `paid_at` diisi;
- `GrantInvitationEntitlement` dijalankan per item yang belum punya entitlement (perpanjangan menumpuk, histori tidak ditimpa);
- audit `order.paid` memuat nomor pesanan, payment id, provider, nominal, dan id entitlement;
- notifikasi email dikirim setelah commit, kegagalan email tidak membatalkan pembayaran.

## 9. Berkas bukti transfer

- Disimpan di disk privat `PAYMENT_PROOF_DISK` (default `local`), path `payment-proofs/{order_id}/...`.
- Nama asli, MIME, dan ukuran ikut disimpan untuk penelusuran.
- Tidak ada route publik. Satu-satunya akses adalah `admin.orders.proof`, yang memeriksa kepemilikan konfirmasi terhadap order, keberadaan berkas, lalu mencatat audit `payment.proof_downloaded`.
- Validasi unggah: MIME jpg/jpeg/png/pdf, batas `PAYMENT_PROOF_MAX_KB` (default 4096), `transferred_at` tidak boleh di masa depan atau sebelum order dibuat.

## 10. Aturan keamanan

- Nominal, kode unik, dan total selalu dihitung server-side.
- Konfirmasi tidak pernah mengaktifkan layanan; hanya keputusan operator.
- Idempotensi: satu payment per order, satu settlement per order, satu konfirmasi `submitted` sekaligus. Penerbitan kode baru membatalkan konfirmasi yang masih menunggu (`rejected` dengan catatan sistem), karena nominalnya tidak lagi cocok dengan order.
- Semua tindakan sensitif diaudit: `payment.checkout_started`, `payment.confirmation_submitted`, `payment.confirmation_verified`, `payment.confirmation_rejected`, `payment.code_regenerated`, `payment.window_expired`, `payment.proof_downloaded`, `order.paid`, `payment.duplicate`.
- Bukti transfer adalah dokumen privat; jangan pernah menaruhnya di disk publik atau melampirkannya ke email.
- Rekening tujuan dibaca dari konfigurasi, tidak pernah dari input pengguna.

## 11. Konfigurasi

```dotenv
PAYMENT_GATEWAY=bank_transfer
PAYMENT_WINDOW_HOURS=24
PAYMENT_BANK_ACCOUNTS="BCA|1234567890|Nama Pemilik;Mandiri|9876543210|Nama Pemilik;BNI|1122334455|Nama Pemilik"
PAYMENT_PROOF_DISK=local
PAYMENT_PROOF_MAX_KB=4096
PAYMENT_FAKE_ENABLED=false
PAYMENT_FAKE_WEBHOOK_SECRET=
```

`PAYMENT_BANK_ACCOUNTS` menerima format `bank|nomor|pemilik`, dipisah titik koma, atau array JSON dengan kunci `bank`, `number`, `holder`. Entri yang tidak lengkap diabaikan, sehingga salah ketik tidak pernah menampilkan rekening setengah jadi.

`PAYMENT_FAKE_ENABLED` bawaannya `false` dan sandbox tetap menolak berjalan di produksi. `PAYMENT_FAKE_WEBHOOK_SECRET` tidak punya nilai cadangan: kosong berarti webhook sandbox tertutup, dan untuk demo lokal nilainya harus diisi eksplisit.

## 12. Operasional

- Scheduler: `php artisan schedule:work` / cron `* * * * * php artisan schedule:run`.
- `payments:expire --limit=200` berjalan tiap 5 menit; jalankan manual bila backlog antrean besar.
- Rekonsiliasi harian: cocokkan daftar `payment_confirmations` yang `submitted` dengan mutasi rekening; jangan biarkan antrean menua.
- Simpan bukti transfer sesuai `docs/PRIVACY_AND_RETENTION.md`; hapus setelah periode sengketa berakhir.

## 13. Acceptance tests

`tests/Feature/BankTransferPaymentTest.php`:

- Order baru mendapat kode 3 digit dan jendela 24 jam.
- Dua order terbuka tidak pernah memakai kode yang sama.
- Checkout mengarah ke halaman instruksi dan menampilkan semua rekening.
- Pelanggan lain tidak dapat membuka halaman instruksi atau mengunggah bukti.
- Unggahan bukti tidak mengaktifkan undangan sampai diverifikasi.
- Nominal tanpa kode unik ditolak.
- Konfirmasi kedua ditolak selama yang pertama menunggu.
- Transfer setelah tenggat ditolak dan status tampil "Kedaluwarsa".
- Kode kedaluwarsa dapat dibuat ulang dengan jendela baru, dan nominal payment mengikuti.
- Penerbitan kode baru membatalkan bukti yang masih menunggu, sehingga pemilik dapat langsung mengunggah bukti untuk nominal baru.
- Jendela yang masih terbuka tidak dapat dibuat ulang.
- Verifikasi admin mengaktifkan undangan sekali, klik kedua 404.
- Penolakan wajib beralasan dan pelanggan dapat mengunggah ulang.
- Konfirmasi dengan nominal yang diubah di luar aplikasi ditolak.
- Bukti hanya dapat diunduh admin.
- `payments:expire` menutup percobaan yang belum dibayar dan tidak menyentuh konfirmasi yang sudah dikirim.

`tests/Feature/PaymentFlowTest.php` tetap menguji mekanika webhook gateway sandbox dan karena itu memilih gateway `fake` secara eksplisit di `setUp()`. Dua kasusnya menjaga gerbang provider: webhook bertanda tangan benar tetap ditolak (`404 unknown_provider`) ketika `PAYMENT_FAKE_ENABLED=false`, dan tanda tangan apa pun ditolak (`403 invalid_signature`) bila `PAYMENT_FAKE_WEBHOOK_SECRET` kosong — termasuk nilai placeholder yang pernah dipakai sebagai fallback konfigurasi.

## 14. Lampiran: provider gateway (sandbox)

Alur webhook lama tetap berlaku bila `PAYMENT_GATEWAY` diarahkan ke provider:

0. `PaymentGatewayRegistry` menolak provider yang tidak aktif: `isEnabled()` harus `true`, dan gateway sandbox selalu `false` di produksi. Webhook untuk provider yang tertutup menjawab `404 unknown_provider` sebelum body dibaca, sehingga gerbangnya tidak bisa terlupa di call site.
1. `CheckoutController` membuat payment dan mengarahkan ke `checkoutUrl` provider.
2. Provider memanggil `POST payments/webhook/{provider}`.
3. `ProcessPaymentNotification` memverifikasi signature, nomor pesanan, amount, dan currency. Tanpa secret yang terisi, verifikasi gagal (fail-closed) dan tidak ada nilai default yang ikut ter-commit ke repositori.
4. Event disimpan unik per `provider_event_id`; duplikat diabaikan.
5. Settle dialihkan ke `MarkOrderPaid` seperti verifikasi manual.
