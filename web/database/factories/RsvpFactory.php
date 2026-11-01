<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RsvpStatus;
use App\Models\Guest;
use App\Models\Rsvp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rsvp>
 */
class RsvpFactory extends Factory
{
    protected $model = Rsvp::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'guest_id' => Guest::factory(),
            'status' => RsvpStatus::Attending,
            'party_size' => 1,
            'message' => null,
            'submitted_at' => now(),
        ];
    }

    public function declined(): static
    {
        return $this->state(fn (): array => [
            'status' => RsvpStatus::Declined,
            'party_size' => 0,
        ]);
    }
}
