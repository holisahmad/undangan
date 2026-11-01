<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archive_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('wedding_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['queued', 'processing', 'ready', 'failed', 'expired'])->default('queued');
            $table->unsignedInteger('asset_count')->default(0);
            $table->unsignedBigInteger('byte_size')->default(0);
            $table->string('object_key', 160)->nullable();
            // The manifest makes the archive auditable: which asset went into
            // which file, even after the assets are deleted
            // (docs/MEDIA_STORAGE.md, section 6).
            $table->json('manifest_json')->nullable();
            $table->string('error_message', 500)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['wedding_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archive_jobs');
    }
};
