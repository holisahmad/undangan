<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_entitlements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('wedding_id')->constrained('weddings')->cascadeOnDelete();
            // Orders ship in a later phase; the column is reserved so entitlement
            // rows can be traced back to the order item that granted them.
            $table->uuid('order_item_id')->nullable();
            $table->enum('entitlement_type', ['invitation', 'gallery', 'archive', 'storage', 'feature']);
            $table->timestamp('starts_at');
            $table->timestamp('expires_at')->nullable();
            $table->json('limits_json')->nullable();
            $table->enum('status', ['pending', 'active', 'expiring', 'expired', 'revoked'])->default('active');
            $table->timestamps();

            $table->index(['wedding_id', 'entitlement_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_entitlements');
    }
};
