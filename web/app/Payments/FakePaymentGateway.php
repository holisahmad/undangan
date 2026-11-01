<?php

declare(strict_types=1);

namespace App\Payments;

use App\Contracts\Payments\PaymentGateway;
use App\Data\Payments\PaymentInitiation;
use App\Data\Payments\PaymentNotification;
use App\Exceptions\Payments\InvalidPaymentPayload;
use App\Exceptions\Payments\PaymentGatewayUnavailable;
use App\Models\Order;
use App\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Sandbox gateway used for local development and tests. There is no network
 * call: "checkout" is a local screen and the webhook payload is signed with a
 * shared secret, so the full verification path is still exercised.
 *
 * Never enable this gateway in production (docs/PAYMENT_FLOW.md, section 4).
 */
final class FakePaymentGateway implements PaymentGateway
{
    public const PROVIDER = 'fake';

    public const SIGNATURE_HEADER = 'X-Fake-Signature';

    public function name(): string
    {
        return self::PROVIDER;
    }

    public function isEnabled(): bool
    {
        // Two independent switches: the operator has to opt in, and production
        // is excluded structurally so a forgotten PAYMENT_FAKE_ENABLED cannot
        // leave a signed-but-fictional provider on the money path.
        return ! app()->isProduction()
            && (bool) config('platform.payments.fake.enabled', false);
    }

    public function createPayment(Order $order, Payment $payment): PaymentInitiation
    {
        $this->ensureAvailable();

        return new PaymentInitiation(
            providerTransactionId: $this->transactionIdFor($payment),
            checkoutUrl: route('checkout.fake.show', $order),
        );
    }

    public function verifySignature(string $rawPayload, ?string $signature): bool
    {
        if (! is_string($signature) || $signature === '') {
            return false;
        }

        // Fail closed: without a configured secret there is no shared key to
        // compare against, so nothing can be accepted as signed.
        if ($this->webhookSecret() === '') {
            return false;
        }

        return hash_equals($this->sign($rawPayload), $signature);
    }

    public function signatureHeader(): string
    {
        return self::SIGNATURE_HEADER;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function parseNotification(array $payload): PaymentNotification
    {
        $data = $payload['data'] ?? null;

        if (! is_array($data)) {
            throw new InvalidPaymentPayload('Payload pembayaran tidak memiliki bagian data.');
        }

        try {
            return PaymentNotification::fromArray([
                'provider' => self::PROVIDER,
                'event_id' => $payload['id'] ?? null,
                'event_type' => $payload['type'] ?? null,
                'transaction_id' => $data['transaction_id'] ?? null,
                'order_number' => $data['order_number'] ?? null,
                'amount' => $data['amount'] ?? null,
                'currency' => $data['currency'] ?? null,
                'status' => $data['status'] ?? null,
                'paid_at' => $data['paid_at'] ?? null,
            ]);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidPaymentPayload($exception->getMessage());
        }
    }

    /**
     * Builds a webhook body exactly as the sandbox provider would send it.
     *
     * @return array<string, mixed>
     */
    public function payloadFor(Order $order, string $status = 'succeeded'): array
    {
        $paidAt = $status === 'succeeded' ? CarbonImmutable::now() : null;

        return [
            'id' => 'evt_'.Str::lower(Str::random(20)),
            'type' => 'payment.'.$status,
            'data' => [
                'transaction_id' => 'trx_'.Str::lower(Str::random(16)),
                'order_number' => $order->public_order_number,
                'amount' => $order->total_amount,
                'currency' => $order->currency,
                'status' => $status,
                'paid_at' => $paidAt?->toIso8601String(),
            ],
        ];
    }

    public function sign(string $rawPayload): string
    {
        return hash_hmac('sha256', $rawPayload, $this->webhookSecret());
    }

    public function transactionIdFor(Payment $payment): string
    {
        return 'trx_'.$payment->getKey();
    }

    private function webhookSecret(): string
    {
        return (string) config('platform.payments.fake.webhook_secret', '');
    }

    private function ensureAvailable(): void
    {
        if (! $this->isEnabled()) {
            throw PaymentGatewayUnavailable::sandboxDisabled();
        }
    }
}
