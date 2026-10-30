<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Wedding;
use App\Models\WeddingPartner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WeddingPartner>
 */
class WeddingPartnerFactory extends Factory
{
    protected $model = WeddingPartner::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'partner_type' => WeddingPartner::TYPE_BRIDE,
            'display_name' => fake()->name(),
            'nickname' => fake()->firstName(),
            'parents' => 'Bapak '.fake()->lastName().' & Ibu '.fake()->lastName(),
            'biography' => null,
            'instagram' => null,
            'display_order' => 0,
        ];
    }

    public function groom(): static
    {
        return $this->state(fn (): array => [
            'partner_type' => WeddingPartner::TYPE_GROOM,
            'display_order' => 1,
        ]);
    }
}
