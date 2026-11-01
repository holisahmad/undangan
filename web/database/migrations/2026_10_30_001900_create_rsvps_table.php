<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rsvps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // One row per guest: the newest response replaces the previous one
            // (docs/adr/008-guests-and-rsvp.md).
            $table->foreignUuid('guest_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('status', ['attending', 'declined', 'maybe']);
            $table->unsignedSmallInteger('party_size')->default(0);
            $table->string('message', 500)->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();

            $table->index(['status', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rsvps');
    }
};
