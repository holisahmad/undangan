<?php

declare(strict_types=1);

namespace App\Support\Payments;

use App\Enums\OrderStatus;
use App\Models\Order;
use RuntimeException;

/**
 * Assigns the three-digit unique code and the payment deadline to an order.
 * The code is unique among orders that still have an open window, so a bank
 * statement line can be matched to exactly one order.
 */
final class PaymentWindow
{
    public function open(Order $order): Order
    {
        $order->forceFill([
            'unique_code' => $this->uniqueCode($order),
            'payment_expires_at' => now()->addHours(BankTransfer::windowHours()),
        ])->save();

        return $order;
    }

    private function uniqueCode(Order $order): int
    {
        $min = max(1, (int) config('platform.payments.bank_transfer.unique_code_min', 100));
        $max = max($min, (int) config('platform.payments.bank_transfer.unique_code_max', 999));

        for ($attempt = 0; $attempt < 60; $attempt++) {
            $candidate = random_int($min, $max);

            $taken = Order::query()
                ->whereKeyNot($order->getKey())
                ->where('unique_code', $candidate)
                ->where('status', OrderStatus::PendingPayment->value)
                ->where(static function ($query): void {
                    $query->whereNull('payment_expires_at')
                        ->orWhere('payment_expires_at', '>', now());
                })
                ->exists();

            if (! $taken) {
                return $candidate;
            }
        }

        throw new RuntimeException('Tidak ada kode unik pembayaran yang tersedia. Coba lagi beberapa saat.');
    }
}
