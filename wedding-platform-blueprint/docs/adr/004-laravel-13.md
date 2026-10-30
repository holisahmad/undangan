# ADR 004: Laravel 13 sebagai Versi Framework

- Status: Accepted
- Date: 2026-10-30

## Context

Pilihan awal pengguna adalah Laravel 12. Saat audit:

- Skeleton `laravel/laravel` v12.10.0 tersedia dari mirror, tetapi Laravel 12 sudah melewati masa bug-fix pada Oktober 2026.
- Mirror Aliyun yang dipilih tidak menyajikan metadata terkini (terakhir 2025-09-01), sedangkan Packagist resmi menyajikan Laravel 13.39.0.
- Framework 13 memakai struktur skeleton yang sama (`bootstrap/app.php`, tidak ada `Http/Kernel.php`), sehingga biaya adaptasi rendah.

## Decision

Gunakan **Laravel 13.39.0** di atas PHP 8.4, dengan PHPUnit 12, Pint, Blade, dan Vite 7 dari skeleton resmi. Requirement di `web/composer.json` memakai `laravel/framework: ^13.17` dan `php: ^8.3`.

Konsekuensi keamanan: karena PHP lokal 8.4.0 belum memenuhi syarat Symfony 8.1 (8.4.1), `composer.json` memuat blok `conflict` untuk `symfony/http-foundation` dan `symfony/mime` agar rilis LTS 7.4 yang sudah dipatch yang dipakai. `composer audit` diverifikasi bersih.

## Consequences

Positive:
- Rilis dengan dukungan bug-fix dan patch keamanan aktif.
- Skeleton resmi terbaru: test, formatter, dan build tooling sudah selaras.
- Tidak ada kode aplikasi lama yang harus dimigrasikan.

Trade-offs:
- Perbedaan kecil dari Laravel 12 (mis. `Controller` tidak lagi menyertakan `AuthorizesRequests`; ditambahkan eksplisit di base controller).
- Dokumentasi pihak ketiga yang masih menyebut Laravel 12 perlu diverifikasi terhadap 13.

## Revisit when

- PHP ditingkatkan ke >= 8.4.1: hapus `conflict` Symfony dan naikkan ke rilis 8.1 yang dipatch.
- Laravel 14 menjadi stabil dan ada kebutuhan fitur konkret.
