<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Actions\Weddings\CreateWedding;
use App\Models\InvitationPublication;
use App\Models\User;
use App\Models\Wedding;
use App\Support\Invitations\InvitationSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvitationPublication>
 */
class InvitationPublicationFactory extends Factory
{
    protected $model = InvitationPublication::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $publishedAt = now();

        return [
            'wedding_id' => Wedding::factory()->published(),
            'version_number' => 1,
            'published_snapshot' => [
                'format_version' => InvitationSnapshot::FORMAT_VERSION,
                'template' => [
                    'slug' => 'classic-ivory',
                    'name' => 'Classic Ivory',
                    'version' => 1,
                    'renderer_key' => 'classic-ivory.v1',
                    'config_schema_version' => '1',
                ],
                'couple' => [[
                    'type' => 'bride',
                    'display_name' => 'Ayu Lestari',
                    'nickname' => 'Ayu',
                    'parents' => null,
                    'biography' => null,
                    'instagram' => null,
                ], [
                    'type' => 'groom',
                    'display_name' => 'Bima Pratama',
                    'nickname' => 'Bima',
                    'parents' => null,
                    'biography' => null,
                    'instagram' => null,
                ]],
                'events' => [],
                'content' => CreateWedding::defaultContent(),
                'published_at' => $publishedAt->toIso8601String(),
                'expires_at' => null,
            ],
            'published_by' => User::factory(),
            'published_at' => $publishedAt,
            'unpublished_at' => null,
            'expires_at' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'expires_at' => now()->subDay(),
        ]);
    }

    public function unpublished(): static
    {
        return $this->state(fn (): array => [
            'unpublished_at' => now(),
        ]);
    }
}
