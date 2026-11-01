<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_invitation_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('guest_id')->constrained()->cascadeOnDelete();
            // The token is a bearer secret: only its SHA-256 hash is stored
            // (docs/DATABASE_SCHEMA.md, section 6).
            $table->char('token_hash', 64)->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->timestamps();

            $table->index(['guest_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_invitation_links');
    }
};
