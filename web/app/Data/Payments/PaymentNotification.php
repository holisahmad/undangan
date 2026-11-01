<?php

declare(strict_types=1);

namespace App\Data\Payments;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * Provider-agnostic payment notification. Adapters translate their own payload
 * into this DTO so the processing rules live in exactly one place.
 */
final readonly class PaymentNotification
{
    public function __construct(
        public string $provider,
        public string $eventId,
        public string $eventType,
        public string $orderNumber,
        public ?string $providerTransactionId,
        public int $amount,
        public string $currency,
        public string $status,
        public ?CarbonImmutable $paidAt = null,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $required = ['provider', 'event_id', 'event_type', 'order_number', 'amount', 'currency', 'status'];

        foreach ($required as $key) {
            if (! array_key_exists($key, $payload)) {
                throw new InvalidArgumentException("Field [{$key}] tidak ditemukan pada payload pembayaran.");
            }
        }

        $paidAt = $payload['paid_at'] ?? null;

        return new self(
            provider: (string) $payload['provider'],
            eventId: (string) $payload['event_id'],
            eventType: (string) $payload['event_type'],
            orderNumber: (string) $payload['order_number'],
            providerTransactionId: isset($payload['transaction_id']) ? (string) $payload['transaction_id'] : null,
            amount: (int) $payload['amount'],
            currency: (string) $payload['currency'],
            status: (string) $payload['status'],
            paidAt: is_string($paidAt) && $paidAt !== '' ? CarbonImmutable::parse($paidAt) : null,
        );
    }

    public function isSucceeded(): bool
    {
        return $this->status === 'succeeded';
    }

    public function isFailure(): bool
    {
        return in_array($this->status, ['failed', 'expired'], true);
    }

    /**
     * Only these fields are persisted; card data and full provider payloads are
     * never stored (docs/PAYMENT_FLOW.md, section 4).
     *
     * @return array<string, scalar|null>
     */
    public function toAuditMetadata(): array
    {
        return [
            'provider' => $this->provider,
            'event_id' => $this->eventId,
            'event_type' => $this->eventType,
            'order_number' => $this->orderNumber,
            'transaction_id' => $this->providerTransactionId,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
        ];
    }
}
