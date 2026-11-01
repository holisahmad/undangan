<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Enums\PaymentConfirmationStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Support\Audit\AuditLogger;
use Illuminate\Console\Command;

/**
 * Closes transfer windows that passed their deadline without a submitted proof.
 * The order stays pending_payment so the customer can reopen it with a fresh
 * three-digit code; only the payment attempt is marked expired.
 */
final class ExpirePaymentWindows extends Command
{
    protected $signature = 'payments:expire {--limit=200 : Maximum orders handled per run}';

    protected $description = 'Expire bank-transfer attempts whose payment window has closed';

    public function handle(AuditLogger $audit): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $orders = Order::query()
            ->where('status', OrderStatus::PendingPayment->value)
            ->whereNotNull('payment_expires_at')
            ->where('payment_expires_at', '<=', now())
            ->whereDoesntHave('confirmations', static fn ($query) => $query->where('status', PaymentConfirmationStatus::Submitted->value))
            ->orderBy('payment_expires_at')
            ->limit($limit)
            ->get();

        foreach ($orders as $order) {
            Payment::query()
                ->where('order_id', $order->getKey())
                ->whereIn('status', [
                    PaymentStatus::Initiated->value,
                    PaymentStatus::Pending->value,
                ])
                ->update(['status' => PaymentStatus::Expired->value]);

            $audit->log(
                action: 'payment.window_expired',
                resourceType: 'order',
                resourceId: $order->getKey(),
                actor: null,
                metadata: [
                    'public_order_number' => $order->public_order_number,
                    'unique_code' => $order->unique_code,
                    'payment_expires_at' => $order->payment_expires_at?->toIso8601String(),
                ],
            );

            $this->line('Kedaluwarsa: '.$order->public_order_number);
        }

        $this->info(sprintf('%d pesanan ditandai kedaluwarsa.', $orders->count()));

        return self::SUCCESS;
    }
}
