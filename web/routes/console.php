<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Retention runs on a schedule so expired archives and quarantined deletions do
// not depend on someone remembering to run the command (docs/MEDIA_STORAGE.md).
Schedule::command('media:cleanup')->hourly()->withoutOverlapping();

// Reconciliation only reports; orphan objects are never deleted automatically.
Schedule::command('media:reconcile')->dailyAt('02:30')->withoutOverlapping();

// Guests who gave an address get at most one reminder per cooldown window
// (config/platform.php: guests.reminder_cooldown_days).
Schedule::command('rsvp:remind')->dailyAt('09:00')->withoutOverlapping();

// Bank-transfer codes stop being valid after the configured window. Only the
// payment attempt is expired; the order stays pending so a new code can be
// issued (config/platform.php: payments.bank_transfer.window_hours).
Schedule::command('payments:expire')->everyFiveMinutes()->withoutOverlapping();
