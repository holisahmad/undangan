<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Guests\SendRsvpReminders;
use App\Models\Wedding;
use Illuminate\Console\Command;

/**
 * Sends RSVP reminders for published invitations. Runs daily from the
 * scheduler; the per-guest cooldown lives in SendRsvpReminders so a manual
 * run cannot pester the same guest twice.
 */
final class RemindRsvp extends Command
{
    protected $signature = 'rsvp:remind
        {--wedding= : Slug undangan tertentu (default: semua undangan yang terbit)}
        {--limit= : Batas pengingat per undangan}
        {--force : Abaikan jeda pengingat}
        {--dry-run : Hitung tanpa mengirim}';

    protected $description = 'Email RSVP reminders to guests who have an address and have not answered';

    public function handle(SendRsvpReminders $reminders): int
    {
        $force = (bool) $this->option('force');
        $dryRun = (bool) $this->option('dry-run');
        $limit = $this->option('limit') !== null ? max(1, (int) $this->option('limit')) : null;

        if ($this->option('wedding') !== null) {
            $wedding = Wedding::query()->where('slug', (string) $this->option('wedding'))->first();

            if ($wedding === null) {
                $this->error('Undangan dengan slug tersebut tidak ditemukan.');

                return self::FAILURE;
            }

            return $this->remindOne($wedding, $reminders, $force, $dryRun, $limit);
        }

        $weddings = Wedding::query()
            ->whereHas('activePublication')
            ->whereHas('guests', static fn ($query) => $query->whereNotNull('contact_email')->whereDoesntHave('rsvp'))
            ->orderBy('slug')
            ->get();

        $sent = 0;
        $failed = 0;

        foreach ($weddings as $wedding) {
            if ($dryRun) {
                $count = $reminders->candidates($wedding, $force);
                $this->info("{$wedding->slug}: {$count} tamu akan diingatkan. (dry-run)");
                $sent += $count;

                continue;
            }

            $result = $reminders->handle($wedding, null, $force, $limit);

            if ($result['eligible'] === 0) {
                $this->line("{$wedding->slug}: tidak ada tamu yang perlu diingatkan.");

                continue;
            }

            $sent += $result['sent'];
            $failed += $result['failed'];

            $this->info("{$wedding->slug}: {$result['sent']} pengingat terkirim, {$result['failed']} gagal.");
        }

        $this->info(sprintf(
            '%s: %d pengingat%s, %d gagal.',
            $dryRun ? 'Pratinjau' : 'Selesai',
            $sent,
            $dryRun ? ' akan dikirim' : ' terkirim',
            $failed,
        ));

        return self::SUCCESS;
    }

    private function remindOne(
        Wedding $wedding,
        SendRsvpReminders $reminders,
        bool $force,
        bool $dryRun,
        ?int $limit,
    ): int {
        if ($dryRun) {
            $count = $reminders->candidates($wedding, $force);
            $this->info("{$wedding->slug}: {$count} tamu akan diingatkan. (dry-run)");

            return self::SUCCESS;
        }

        $result = $reminders->handle($wedding, null, $force, $limit);

        if ($result['unavailable']) {
            $this->error("{$wedding->slug}: undangan belum terbit, pengingat tidak dikirim.");

            return self::FAILURE;
        }

        if ($result['eligible'] === 0) {
            $this->line("{$wedding->slug}: tidak ada tamu yang perlu diingatkan.");

            return self::SUCCESS;
        }

        $this->info("{$wedding->slug}: {$result['sent']} pengingat terkirim, {$result['failed']} gagal.");

        return self::SUCCESS;
    }
}
