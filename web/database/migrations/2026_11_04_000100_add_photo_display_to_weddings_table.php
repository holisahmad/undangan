<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('weddings', function (Blueprint $table) {
            // Whether the invitation shows the couple's own pre-wedding photos
            // or an initials monogram. A real invitation never falls back to
            // stock photography, so "no photo" is a deliberate choice, not a
            // broken image.
            $table->string('photo_display', 20)->default('prewedding')->after('internal_title');
            // The photo used for the opening section when the couple uploaded
            // more than one; the rest follow in upload order.
            $table->uuid('cover_media_asset_id')->nullable()->after('photo_display');
            $table->foreign('cover_media_asset_id')->references('id')->on('media_assets')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('weddings', function (Blueprint $table) {
            $table->dropForeign(['cover_media_asset_id']);
            $table->dropColumn(['photo_display', 'cover_media_asset_id']);
        });
    }
};
