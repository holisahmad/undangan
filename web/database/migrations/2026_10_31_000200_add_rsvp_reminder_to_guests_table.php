<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            // Last time a reminder was emailed, so scheduled runs respect the
            // cooldown instead of nagging the same guest every day.
            $table->timestamp('rsvp_reminded_at')->nullable()->after('contact_email');

            $table->index(['wedding_id', 'rsvp_reminded_at']);
        });
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropIndex(['wedding_id', 'rsvp_reminded_at']);
            $table->dropColumn('rsvp_reminded_at');
        });
    }
};
