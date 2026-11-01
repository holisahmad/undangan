<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Payments\ProcessPaymentNotification;
use App\Exceptions\Payments\InvalidPaymentSignature;
use App\Models\Order;
use App\Payments\FakePaymentGateway;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use JsonException;

/**
 * Local demonstration tool: sends a signed sandbox notification through the same
 * processor the webhook endpoint uses, so order, payment and entitlement all
 * follow the production rules. Refuses to run in production.
 */
final class SimulatePayment extends Command
{
    protected $signature = 'payments:simulate
        {order : Public order number or order UUID}
        {--status=succeeded : succeeded, failed, or expired}';

    protected $description = 'Send a signed sandbox payment notification for an order (local and testing only)';

    public function handle(ProcessPaymentNotification $process, FakePaymentGateway $gateway): int
    {
        // The same switch the checkout screen and the webhook honour, so the
        // command cannot report success for a provider that is closed.
        if (! $gateway->isEnabled()) {
            $this->error('Sandbox pembayaran tidak aktif. Perintah ini hanya tersedia pada lingkungan non-produksi dengan PAYMENT_FAKE_ENABLED=true.');

            return self::FAILURE;
        }

        $order = $this->resolveOrder((string) $this->argument('order'));

        if ($order === null) {
            $this->error('Pesanan tidak ditemukan.');

            return self::FAILURE;
        }

        $status = (string) $this->option('status');

        if (! in_array($status, ['succeeded', 'failed', 'expired'], true)) {
            $this->error('Status harus salah satu dari: succeeded, failed, expired.');

            return self::FAILURE;
        }

        $payload = $gateway->payloadFor($order, $status);

        try {
            $raw = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (JsonException) {
            $this->error('Payload simulasi tidak dapat dibuat.');

            return self::FAILURE;
        }

        try {
            $event = $process->handle(
                provider: FakePaymentGateway::PROVIDER,
                rawPayload: $raw,
                signature: $gateway->sign($raw),
                payload: $payload,
            );
        } catch (InvalidPaymentSignature) {
            $this->error('PAYMENT_FAKE_WEBHOOK_SECRET belum diisi, jadi tanda tangan sandbox tidak dapat diverifikasi.');

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Event %s diproses dengan status %s untuk pesanan %s.',
            $event->provider_event_id,
            $event->processing_status->value,
            $order->public_order_number,
        ));

        return self::SUCCESS;
    }

    private function resolveOrder(string $identifier): ?Order
    {
        $query = Order::query();

        return Str::isUuid($identifier)
            ? $query->whereKey($identifier)->first() ?? $query->where('public_order_number', $identifier)->first()
            : $query->where('public_order_number', $identifier)->first();
    }
}
