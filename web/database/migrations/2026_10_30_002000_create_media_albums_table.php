<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_albums', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('wedding_id')->constrained()->cascadeOnDelete();
            $table->string('title', 80);
            // Visibility decides whether the album may appear on the public
            // gallery page; it is never inferred from the URL
            // (docs/MEDIA_STORAGE.md, section 4).
            $table->enum('visibility', ['private', 'public'])->default('private');
            $table->timestamps();

            $table->unique(['wedding_id', 'title']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_albums');
    }
};
