<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\InvitationTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<InvitationTemplate>
 */
class InvitationTemplateFactory extends Factory
{
    protected $model = InvitationTemplate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'Template '.fake()->unique()->word();

        return [
            'slug' => Str::slug($name),
            'name' => $name,
            'category' => 'classic',
            'status' => InvitationTemplate::STATUS_ACTIVE,
            'thumbnail_object_key' => null,
            'description' => null,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (): array => [
            'status' => InvitationTemplate::STATUS_ARCHIVED,
        ]);
    }
}
