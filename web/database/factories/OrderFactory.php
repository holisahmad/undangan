<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'public_order_number' => 'INV-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
            'user_id' => User::factory(),
            'wedding_id' => Wedding::factory(),
            'status' => OrderStatus::PendingPayment,
            'currency' => 'IDR',
            'subtotal_amount' => 99000,
            'discount_amount' => 0,
            'total_amount' => 99000,
            'unique_code' => random_int(100, 999),
            'payment_expires_at' => now()->addDay(),
            'paid_at' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (): array => [
            'status' => OrderStatus::Paid,
            'paid_at' => now(),
        ]);
    }
}
