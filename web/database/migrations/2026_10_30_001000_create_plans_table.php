<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            // Amounts are integers in the currency's smallest unit. IDR has no
            // minor unit, so 99000 means Rp 99.000 (docs/BUSINESS_MODEL.md, 4).
            $table->unsignedInteger('price_amount');
            $table->char('currency', 3)->default('IDR');
            $table->unsignedSmallInteger('duration_months');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
