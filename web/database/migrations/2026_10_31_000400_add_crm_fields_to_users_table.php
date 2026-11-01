<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Operator-only CRM fields: an internal note and free-form tags used
            // for segmentation. Customers never see or edit them.
            $table->text('crm_note')->nullable()->after('suspension_reason');
            $table->json('crm_tags')->nullable()->after('crm_note');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['crm_note', 'crm_tags']);
        });
    }
};
