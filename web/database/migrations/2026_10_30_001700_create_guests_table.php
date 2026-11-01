<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('wedding_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('guest_group_id')->nullable()->constrained('guest_groups')->nullOnDelete();
            $table->string('display_name', 120);
            // Contact data is optional and never exposed on public pages
            // (docs/PRD.md, section 7.3).
            $table->string('contact_phone', 30)->nullable();
            $table->string('contact_email', 190)->nullable();
            $table->unsignedSmallInteger('allowed_party_size')->default(1);
            $table->timestamps();

            $table->index(['wedding_id', 'guest_group_id']);
            $table->index(['wedding_id', 'display_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
