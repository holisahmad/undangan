<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GuestGroup;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuestGroup>
 */
class GuestGroupFactory extends Factory
{
    protected $model = GuestGroup::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'name' => 'Grup '.fake()->unique()->word(),
        ];
    }
}
