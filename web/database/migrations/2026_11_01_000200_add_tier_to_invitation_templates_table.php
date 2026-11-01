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
            $table->string('tier', 20)->default(InvitationTemplate::TIER_ESSENTIAL)->after('category');
            $table->string('badge', 24)->nullable()->after('tier');
            $table->unsignedInteger('sort_order')->default(0)->after('badge');

            // The public catalogue filters by status and tier, then orders by the
            // curated position, so both are covered by one index prefix.
            $table->index(['status', 'tier', 'sort_order'], 'invitation_templates_catalog_index');
        });
    }

    public function down(): void
    {
        Schema::table('invitation_templates', function (Blueprint $table) {
            $table->dropIndex('invitation_templates_catalog_index');
            $table->dropColumn(['tier', 'badge', 'sort_order']);
        });
    }
};
