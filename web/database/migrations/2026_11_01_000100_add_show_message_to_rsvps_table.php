<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Opt-in switch for the public wish wall.
 *
 * Existing rows stay private (default false) because those guests wrote their
 * message before the wall existed and never agreed to have it published.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rsvps', function (Blueprint $table): void {
            $table->boolean('show_message')->default(false)->after('message');
        });
    }

    public function down(): void
    {
        Schema::table('rsvps', function (Blueprint $table): void {
            $table->dropColumn('show_message');
        });
    }
};
