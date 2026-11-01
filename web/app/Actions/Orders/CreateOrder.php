<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use App\Models\Wedding;
use App\Support\Audit\AuditLogger;
use App\Support\Payments\PaymentWindow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Creates an order from a catalogue plan. Prices, currency and duration are
 * read from the database, never from the request
 * (docs/PAYMENT_FLOW.md, section 4).
 */
final class CreateOrder
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly PaymentWindow $window,
    ) {}

    public function handle(User $actor, Wedding $wedding, Plan $plan): Order
    {
        if (! $plan->isActive()) {
            throw new InvalidArgumentException('Paket tidak tersedia untuk pembelian.');
        }

        return DB::transaction(function () use ($actor, $wedding, $plan): Order {
            $price = $plan->price_amount;

            $order = Order::create([
                'public_order_number' => $this->nextOrderNumber(),
                'user_id' => $actor->getKey(),
                'wedding_id' => $wedding->getKey(),
                'status' => OrderStatus::PendingPayment,
                'currency' => $plan->currency,
                'subtotal_amount' => $price,
                'discount_amount' => 0,
                'total_amount' => $price,
            ]);

            $order->items()->create([
                'plan_id' => $plan->getKey(),
                'quantity' => 1,
                'unit_price_amount' => $price,
                'total_amount' => $price,
                'configuration_snapshot' => [
                    'plan_code' => $plan->code,
                    'plan_name' => $plan->name,
                    'duration_months' => $plan->duration_months,
                    'currency' => $plan->currency,
                    'price_amount' => $price,
                    'features' => $plan->features
                        ->mapWithKeys(static fn ($feature): array => [$feature->feature_key => $feature->value_json])
                        ->all(),
                ],
            ]);

            $this->window->open($order);

            $this->audit->log(
                action: 'order.created',
                resourceType: 'order',
                resourceId: $order->getKey(),
                actor: $actor,
                metadata: [
                    'public_order_number' => $order->public_order_number,
                    'wedding_id' => $wedding->getKey(),
                    'plan_code' => $plan->code,
                    'total_amount' => $price,
                    'unique_code' => $order->unique_code,
                    'amount_due' => $order->amountDue(),
                    'payment_expires_at' => $order->payment_expires_at?->toIso8601String(),
                    'currency' => $plan->currency,
                ],
            );

            return $order->load('items.plan');
        });
    }

    private function nextOrderNumber(): string
    {
        do {
            $number = 'INV-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Order::query()->where('public_order_number', $number)->exists());

        return $number;
    }
}
