<?php

declare(strict_types=1);

use App\Models\InvitationTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The catalogue needs to say which package a design belongs to. A tier is
 * marketing metadata, not code: the renderer still comes from
 * template_versions.renderer_key and an unknown tier never unlocks a template
 * (docs/DATABASE_SCHEMA.md, section 4).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitation_templates', function (Blueprint $table) {
            if (! Schema::hasColumn('invitation_templates', 'tier')) {
                $table->string('tier', 20)->default(InvitationTemplate::TIER_ESSENTIAL)->after('category');
            }
            if (! Schema::hasColumn('invitation_templates', 'badge')) {
                $table->string('badge', 24)->nullable()->after('tier');
            }
            if (! Schema::hasColumn('invitation_templates', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('badge');
            }

            try {
                $table->index(['status', 'tier', 'sort_order'], 'invitation_templates_catalog_index');
            } catch (\Throwable) {
                // Index may already exist
            }
        });
    }

    public function down(): void
    {
        Schema::table('invitation_templates', function (Blueprint $table) {
            try {
                $table->dropIndex('invitation_templates_catalog_index');
            } catch (\Throwable) {
            }
            $table->dropColumn(['tier', 'badge', 'sort_order']);
        });
    }
};
