<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The landing page now offers services beyond the invitation itself, so an
 * enquiry can arrive already knowing what it is about. One short label per lead
 * and nothing more: still no IP, user agent, or browsing history
 * (docs/PRIVACY_AND_RETENTION.md).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (! Schema::hasColumn('leads', 'service_interest')) {
                $table->string('service_interest', 60)->nullable()->after('source');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'service_interest')) {
                $table->dropColumn('service_interest');
            }
        });
    }
};
