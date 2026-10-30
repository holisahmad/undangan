<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EntitlementStatus;
use App\Enums\EntitlementType;
use App\Models\ServiceEntitlement;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceEntitlement>
 */
class ServiceEntitlementFactory extends Factory
{
    protected $model = ServiceEntitlement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'order_item_id' => null,
            'entitlement_type' => EntitlementType::Invitation,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonths(12),
            'limits_json' => null,
            'status' => EntitlementStatus::Active,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'starts_at' => now()->subMonths(13),
            'expires_at' => now()->subMonth(),
        ]);
    }
}
