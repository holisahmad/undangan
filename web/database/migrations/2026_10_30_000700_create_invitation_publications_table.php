<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitation_publications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('wedding_id')->constrained('weddings')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->json('published_snapshot');
            $table->foreignId('published_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('published_at');
            $table->timestamp('unpublished_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['wedding_id', 'version_number']);
            $table->index(['wedding_id', 'unpublished_at', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_publications');
    }
};
