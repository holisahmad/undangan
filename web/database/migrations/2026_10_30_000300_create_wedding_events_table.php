<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wedding_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('wedding_id')->constrained('weddings')->cascadeOnDelete();
            $table->enum('event_type', ['akad', 'resepsi', 'other'])->default('resepsi');
            $table->string('title', 120);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->string('timezone', 64)->default('Asia/Jakarta');
            $table->string('venue_name', 160);
            $table->text('address')->nullable();
            $table->string('map_url', 2048)->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['wedding_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wedding_events');
    }
};
