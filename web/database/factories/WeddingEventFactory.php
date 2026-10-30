<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Wedding;
use App\Models\WeddingEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WeddingEvent>
 */
class WeddingEventFactory extends Factory
{
    protected $model = WeddingEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now()->addMonths(2)->setTime(9, 0);

        return [
            'wedding_id' => Wedding::factory(),
            'event_type' => 'resepsi',
            'title' => 'Resepsi',
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHours(4),
            'timezone' => 'Asia/Jakarta',
            'venue_name' => 'Gedung '.fake()->company(),
            'address' => fake()->streetAddress().', '.fake()->city(),
            'map_url' => null,
            'display_order' => 0,
        ];
    }
}
