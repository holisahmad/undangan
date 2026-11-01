<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('wedding_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('album_id')->nullable()->constrained('media_albums')->nullOnDelete();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Object keys never come from the client filename; the original
            // filename is kept for display only (docs/MEDIA_STORAGE.md, 3).
            $table->string('object_key', 160)->unique();
            $table->string('preview_object_key', 160)->nullable();
            $table->string('thumbnail_object_key', 160)->nullable();
            $table->string('original_filename', 255);
            $table->string('mime_type', 100);
            $table->char('checksum_sha256', 64);
            $table->unsignedBigInteger('byte_size');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->enum('status', [
                'pending_upload',
                'uploaded',
                'processing',
                'ready',
                'quarantined',
                'deletion_pending',
                'deleted',
                'failed',
            ])->default('uploaded');
            // Storage reconciliation needs to find objects whose metadata is
            // gone, so deletions keep the row until cleanup confirms the file.
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();

            $table->index(['wedding_id', 'status']);
            $table->index(['wedding_id', 'album_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_assets');
    }
};
