<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestMailSend extends Command
{
    protected $signature = 'mail:test {--to= : Email tujuan (default: MAIL_FROM_ADDRESS)}';

    protected $description = 'Kirim email test untuk verifikasi konfigurasi Resend';

    public function handle(): int
    {
        $to = $this->option('to') ?: config('mail.from.address');

        $this->info("Mengirim email test ke: {$to}");
        $this->info('Mailer : '.config('mail.default'));
        $this->info('From   : '.config('mail.from.address'));
        $redirect = config('mail.redirect_to');
        if ($redirect) {
            $this->warn("MAIL_REDIRECT_TO aktif — semua email dialihkan ke: {$redirect}");
        }

        try {
            Mail::raw(
                "Halo!\n\nIni adalah email test dari Wedly Studio.\n\nKonfigurasi Resend berhasil! Email transaksional (verifikasi akun, notifikasi order, RSVP reminder, dll) siap dikirim ke pelanggan.\n\nSalam,\nWedly Studio",
                function ($message) use ($to) {
                    $message->to($to)
                        ->subject('✉️ Test Email Wedly — Resend Berhasil');
                }
            );

            $this->info('✓ Email berhasil dikirim! Cek inbox: '.($redirect ?: $to));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('✗ Gagal: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
