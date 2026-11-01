<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('weddings', function (Blueprint $table) {
            // Opt-in portfolio flag. Only the owner can enable it and only while
            // a publication is active (docs/SECURITY.md: invitations are private
            // by default).
            $table->boolean('showcase_enabled')->default(false)->after('lifecycle_status');
            $table->timestamp('showcase_enabled_at')->nullable()->after('showcase_enabled');

            $table->index('showcase_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('weddings', function (Blueprint $table) {
            $table->dropIndex(['showcase_enabled']);
            $table->dropColumn(['showcase_enabled', 'showcase_enabled_at']);
        });
    }
};
