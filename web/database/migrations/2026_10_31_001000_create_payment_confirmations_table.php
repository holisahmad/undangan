<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A manual bank transfer has no provider webhook, so the customer supplies the
 * transfer proof and an operator verifies it. Each row is one submission; the
 * proof file lives on a private disk and is only served to admins.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_confirmations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignUuid('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sender_name', 120);
            $table->string('sender_bank', 60);
            $table->date('transferred_at');
            $table->unsignedInteger('amount');
            $table->text('note')->nullable();
            $table->string('proof_path');
            $table->string('proof_original_name')->nullable();
            $table->string('proof_mime', 100)->nullable();
            $table->unsignedInteger('proof_size')->nullable();
            $table->enum('status', ['submitted', 'verified', 'rejected'])->default('submitted');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_confirmations');
    }
};
