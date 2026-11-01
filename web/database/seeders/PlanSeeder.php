<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Catalogue for local demos and tests. Prices, durations and quotas are product
 * hypotheses (docs/BUSINESS_MODEL.md, section 4) and must be validated before
 * they are shown to real customers.
 */
class PlanSeeder extends Seeder
{
    /**
     * @var list<array{code: string, name: string, price_amount: int, duration_months: int, features: array<string, mixed>}>
     */
    private const PLANS = [
        [
            'code' => 'essential',
            'name' => 'Essential',
            'price_amount' => 99000,
            'duration_months' => 12,
            'features' => [
                // Each package states the most exclusive template tier it opens.
                'template_tier' => ['tier' => 'essential'],
                'guest_limit' => ['limit' => 300],
                'gallery_photos' => ['limit' => 20],
                'custom_domain' => ['enabled' => false],
            ],
        ],
        [
            'code' => 'signature',
            'name' => 'Signature',
            'price_amount' => 199000,
            'duration_months' => 18,
            'features' => [
                'template_tier' => ['tier' => 'signature'],
                'guest_limit' => ['limit' => 800],
                'gallery_photos' => ['limit' => 100],
                'custom_domain' => ['enabled' => false],
            ],
        ],
        [
            'code' => 'premium',
            'name' => 'Premium',
            'price_amount' => 349000,
            'duration_months' => 24,
            'features' => [
                'template_tier' => ['tier' => 'all'],
                'guest_limit' => ['limit' => 2000],
                'gallery_photos' => ['limit' => -1],
                'custom_domain' => ['enabled' => true],
            ],
        ],
    ];

    public function run(): void
    {
        foreach (self::PLANS as $definition) {
            $plan = Plan::query()->updateOrCreate(
                ['code' => $definition['code']],
                [
                    'name' => $definition['name'],
                    'price_amount' => $definition['price_amount'],
                    'currency' => 'IDR',
                    'duration_months' => $definition['duration_months'],
                    'status' => Plan::STATUS_ACTIVE,
                ],
            );

            foreach ($definition['features'] as $key => $value) {
                $plan->features()->updateOrCreate(
                    ['feature_key' => $key],
                    ['value_json' => $value],
                );
            }
        }
    }
}
