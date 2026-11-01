<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AlbumVisibility;
use App\Models\MediaAlbum;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MediaAlbum>
 */
class MediaAlbumFactory extends Factory
{
    protected $model = MediaAlbum::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'title' => fake()->unique()->words(2, true),
            'visibility' => AlbumVisibility::Private,
        ];
    }

    public function public(): static
    {
        return $this->state(fn (): array => ['visibility' => AlbumVisibility::Public]);
    }
}
