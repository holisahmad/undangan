<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'plan_id' => Plan::factory(),
            'quantity' => 1,
            'unit_price_amount' => 99000,
            'total_amount' => 99000,
            'configuration_snapshot' => ['duration_months' => 12],
        ];
    }
}
