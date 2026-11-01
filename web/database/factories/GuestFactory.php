<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Guest;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guest>
 */
class GuestFactory extends Factory
{
    protected $model = Guest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'guest_group_id' => null,
            'display_name' => fake()->name(),
            'contact_phone' => fake()->numerify('08##########'),
            'contact_email' => null,
            'allowed_party_size' => 2,
        ];
    }

    public function withPartySize(int $size): static
    {
        return $this->state(fn (): array => ['allowed_party_size' => $size]);
    }
}
