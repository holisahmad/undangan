<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wedding_partners', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('wedding_id')->constrained('weddings')->cascadeOnDelete();
            $table->enum('partner_type', ['bride', 'groom', 'other'])->default('bride');
            $table->string('display_name', 120);
            $table->string('nickname', 60)->nullable();
            $table->string('parents', 200)->nullable();
            $table->text('biography')->nullable();
            $table->string('instagram', 60)->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['wedding_id', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wedding_partners');
    }
};
