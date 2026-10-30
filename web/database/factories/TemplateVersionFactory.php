<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\InvitationTemplate;
use App\Models\TemplateVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TemplateVersion>
 */
class TemplateVersionFactory extends Factory
{
    protected $model = TemplateVersion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'template_id' => InvitationTemplate::factory(),
            'version' => 1,
            'renderer_key' => 'classic-ivory.v1',
            'config_schema_version' => '1',
            'status' => 'published',
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => [
            'status' => 'draft',
        ]);
    }
}
