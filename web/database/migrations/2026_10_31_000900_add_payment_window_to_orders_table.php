<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bank transfer payments need two facts the order did not have: the unique
 * three-digit suffix that makes each transfer identifiable, and the moment the
 * payment window closes (docs/PAYMENT_FLOW.md, section 3).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedSmallInteger('unique_code')->nullable()->after('total_amount');
            $table->timestamp('payment_expires_at')->nullable()->after('unique_code');

            $table->index(['status', 'payment_expires_at']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status', 'payment_expires_at']);
            $table->dropColumn(['unique_code', 'payment_expires_at']);
        });
    }
};
