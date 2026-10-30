<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitation_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug', 80)->unique();
            $table->string('name', 120);
            $table->string('category', 60)->default('classic');
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            $table->string('thumbnail_object_key', 512)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['status', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_templates');
    }
};
