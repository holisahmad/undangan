# AI Agent Rules — Mandatory

## 1. Mission

Bangun Wedding Experience Platform sesuai dokumen proyek dengan implementasi bertahap, maintainable, aman, dan dapat diuji. Jangan mengoptimalkan untuk banyaknya file atau fitur; optimalkan untuk kebenaran perilaku dan kemudahan pemeliharaan.

## 2. Before coding

1. Baca `README.md` dan dokumen relevan.
2. Periksa struktur repository, git status, stack, package manager, test commands, dan fitur yang sudah ada.
3. Jangan menganggap repo kosong.
4. Identifikasi konflik antara dokumen dan implementasi yang sudah ada.
5. Jika ada keputusan kritis yang belum ditentukan, buat daftar opsi dan rekomendasi; jangan menebak provider, credential, atau kebijakan legal.
6. Buat rencana dengan file yang akan berubah, migration impact, risiko, dan test.

## 3. Implementation discipline

- Kerjakan satu fase atau satu issue dalam satu waktu.
- Gunakan arsitektur modular monolith kecuali ADR yang disetujui menentukan lain.
- Pisahkan domain, application/service, persistence, dan presentation sesuai kebutuhan stack.
- Hindari giant controller, business logic di UI, duplicate logic, dan abstraksi prematur.
- Gunakan naming yang konsisten.
- Validasi semua input di server.
- Gunakan database constraint untuk invariant penting.
- Tambahkan test untuk bug yang diperbaiki.
- Update API/docs saat kontrak berubah.
- Jangan melakukan refactor besar yang tidak relevan dengan tugas.

## 4. Security musts

- Otorisasi per resource pada server.
- Jangan percaya ID dari client.
- UUID bukan authorization.
- Jangan simpan secret di source code.
- Jangan log password, auth token, signed URL, atau PII berlebihan.
- Verifikasi webhook dan idempotency.
- Upload harus divalidasi sebelum public.
- Signed URL berumur pendek.
- Terapkan rate limit pada endpoint publik sensitif.
- Tindakan admin sensitif harus diaudit.
- Uji tenant isolation.

## 5. Business invariants

- Draft dan publication adalah state yang berbeda.
- Perubahan draft tidak mengubah publication sampai publish.
- Template memiliki versi.
- Masa aktif invitation berbeda dari gallery access dan media retention.
- Payment status tidak ditentukan browser.
- Entitlement harus berdasarkan aturan server-side dan transaksi yang diverifikasi.
- File disimpan di object storage; database menyimpan metadata.
- File yang expired tidak otomatis dihapus sebelum kebijakan retensi terpenuhi.
- Tidak ada janji storage permanen tanpa produk dan biaya yang mendukungnya.
- Public response hanya memuat data yang diizinkan.

## 6. Database and migrations

- Buat migration kecil dan dapat ditinjau.
- Gunakan foreign key, unique constraints, dan indexes yang relevan.
- Hindari destructive migration tanpa rencana migrasi data dan rollback/forward recovery.
- Jangan mengubah data production melalui script ad hoc tanpa prosedur.
- Gunakan decimal/numeric untuk uang.
- Normalisasi data inti; JSON hanya untuk konfigurasi fleksibel yang tervalidasi.
- Jangan menyimpan file biner besar di tabel biasa.
- Pertimbangkan timezone dan status transitions.

## 7. UI/UX requirements

- Mobile-first.
- Gunakan design tokens dan komponen yang konsisten.
- Setiap alur penting memiliki loading, empty, error, success, dan unauthorized/expired states yang sesuai.
- Form harus mempertahankan input saat error.
- Preview harus jelas berbeda dari publication.
- Jangan menampilkan fitur seolah aktif jika backend belum tersedia.
- Hindari placeholder content pada production.
- Perhatikan aksesibilitas dan reduced motion.
- Jangan menambah animasi atau dependency hanya demi terlihat modern.

## 8. Testing and commands

Sebelum selesai:
- Jalankan formatter/linter yang tersedia.
- Jalankan type-check/static analysis.
- Jalankan test relevan.
- Jalankan build jika memungkinkan.
- Laporkan perintah dan hasil aktual.
- Jika test tidak bisa dijalankan, jelaskan penyebabnya.
- Jangan menyatakan lulus jika tidak dijalankan.
- Jangan menonaktifkan test untuk menyembunyikan bug.

## 9. Dependency policy

Sebelum menambah dependency:
- Jelaskan kebutuhan.
- Periksa maintenance, lisensi, kompatibilitas, dan ukuran.
- Pilih dependency minimal.
- Jangan mengganti stack hanya karena preferensi agent.
- Jangan membuat integrasi provider tanpa memeriksa dokumentasi resmi dan sandbox.

## 10. External services

- Jangan menebak API, URL, webhook signature, atau behavior provider.
- Buat adapter/interface bila memudahkan testing dan penggantian provider.
- Gunakan sandbox/mock untuk development.
- Secret production tidak boleh diminta untuk ditulis ke file repository.
- Jika akses provider belum tersedia, implementasikan interface dan test dengan mock; dokumentasikan integrasi yang belum selesai.

## 11. Git and changes

- Jangan menghapus perubahan pengguna yang sudah ada.
- Periksa `git status` sebelum mengubah file.
- Jangan menjalankan perintah destruktif tanpa izin eksplisit.
- Jangan force-push, reset hard, atau menghapus data.
- Hindari perubahan format massal yang tidak relevan.
- Sajikan ringkasan perubahan dan risiko.

## 12. Communication style

- Singkat, langsung, dan teknis.
- Jelaskan alasan keputusan secara ringkas.
- Pisahkan fakta, asumsi, dan keputusan yang perlu persetujuan.
- Jangan mengklaim pekerjaan selesai tanpa bukti.
- Di akhir tugas laporkan:
  1. Perubahan.
  2. File utama.
  3. Test yang dijalankan dan hasil.
  4. Risiko atau pekerjaan tersisa.
  5. Langkah berikutnya.

## 13. Definition of Done

- Acceptance criteria terpenuhi.
- Test relevan lulus.
- Otorisasi dan validasi diterapkan.
- Tidak ada secret.
- Migrasi aman dan terdokumentasi.
- UI state lengkap.
- Dokumentasi diperbarui.
- Perubahan tidak merusak fitur terkait.
- Ringkasan hasil akurat.
