<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => Str::lower(Str::random(10)),
            'name' => 'Paket '.fake()->unique()->word(),
            'price_amount' => 99000,
            'currency' => 'IDR',
            'duration_months' => 12,
            'status' => Plan::STATUS_ACTIVE,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'status' => Plan::STATUS_INACTIVE,
        ]);
    }
}
