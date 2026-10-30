<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\InvitationTemplate;
use App\Models\TemplateVersion;
use Illuminate\Database\Seeder;

/**
 * Seeds the template version that ships with the first phase. Templates are
 * code, not data: the renderer_key must exist in TemplateRegistry.
 */
final class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        $template = InvitationTemplate::updateOrCreate(
            ['slug' => 'classic-ivory'],
            [
                'name' => 'Classic Ivory',
                'category' => 'classic',
                'status' => InvitationTemplate::STATUS_ACTIVE,
                'description' => 'Undangan bergaya klasik dengan tipografi serif.',
            ],
        );

        TemplateVersion::updateOrCreate(
            ['template_id' => $template->getKey(), 'version' => 1],
            [
                'renderer_key' => 'classic-ivory.v1',
                'config_schema_version' => '1',
                'status' => 'published',
            ],
        );
    }
}
