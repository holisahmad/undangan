<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Testimonial;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Testimonial>
 */
class TestimonialFactory extends Factory
{
    protected $model = Testimonial::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'user_id' => User::factory(),
            'rating' => fake()->numberBetween(4, 5),
            'quote' => 'Undangan rapi dan tamu mudah mengonfirmasi kehadiran lewat tautan pribadi.',
            'status' => Testimonial::STATUS_PENDING,
            'moderated_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => Testimonial::STATUS_APPROVED,
            'moderated_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (): array => [
            'status' => Testimonial::STATUS_REJECTED,
            'moderated_at' => now(),
        ]);
    }
}
