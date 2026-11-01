<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'provider' => 'fake',
            'provider_transaction_id' => null,
            'status' => PaymentStatus::Initiated,
            'amount' => 99000,
            'currency' => 'IDR',
            'idempotency_key' => 'pay_'.Str::lower(Str::random(16)),
            'paid_at' => null,
        ];
    }

    public function succeeded(): static
    {
        return $this->state(fn (): array => [
            'status' => PaymentStatus::Succeeded,
            'provider_transaction_id' => 'trx_'.Str::lower(Str::random(12)),
            'paid_at' => now(),
        ]);
    }
}
