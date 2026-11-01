<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('public_order_number')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // Deviation from docs/DATABASE_SCHEMA.md: an order always belongs to
            // one wedding, so the reference is explicit instead of living only
            // inside configuration_snapshot (docs/adr/007-orders-and-payments.md).
            $table->foreignUuid('wedding_id')->constrained('weddings')->cascadeOnDelete();
            $table->enum('status', [
                'draft',
                'pending_payment',
                'paid',
                'payment_failed',
                'cancelled',
                'refunded',
            ])->default('pending_payment');
            $table->char('currency', 3);
            $table->unsignedInteger('subtotal_amount');
            $table->unsignedInteger('discount_amount')->default(0);
            $table->unsignedInteger('total_amount');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['wedding_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
