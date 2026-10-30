<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weddings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('slug', 80)->unique();
            $table->string('internal_title', 160);
            $table->enum('lifecycle_status', ['draft', 'published', 'unpublished', 'expired'])
                ->default('draft');
            $table->timestamps();

            $table->index(['owner_user_id', 'lifecycle_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weddings');
    }
};
