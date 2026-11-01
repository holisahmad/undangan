<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignUuid('plan_id')->constrained('plans');
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->unsignedInteger('unit_price_amount');
            $table->unsignedInteger('total_amount');
            // Frozen copy of the plan at purchase time: price and feature limits
            // must not change retroactively when the catalogue is edited.
            $table->json('configuration_snapshot')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
