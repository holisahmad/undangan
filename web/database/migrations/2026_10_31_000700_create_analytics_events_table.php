<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 60);
            $table->foreignUuid('wedding_id')->nullable()->constrained()->nullOnDelete();
            $table->json('properties')->nullable();
            // Deliberately no IP, user agent, or user id: the funnel is
            // aggregate-only and cannot be traced back to a person.
            $table->timestamp('occurred_at');

            $table->index(['name', 'occurred_at']);
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
