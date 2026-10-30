<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\WeddingLifecycleStatus;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Wedding>
 */
class WeddingFactory extends Factory
{
    protected $model = Wedding::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = 'Pernikahan '.fake()->firstName().' & '.fake()->firstName();

        return [
            'owner_user_id' => User::factory(),
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(6)),
            'internal_title' => $title,
            'lifecycle_status' => WeddingLifecycleStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'lifecycle_status' => WeddingLifecycleStatus::Published,
        ]);
    }
}
