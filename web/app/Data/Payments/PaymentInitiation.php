<?php

declare(strict_types=1);

namespace App\Data\Payments;

/**
 * Result of asking a gateway to start a transaction for an order.
 */
final readonly class PaymentInitiation
{
    public function __construct(
        public string $providerTransactionId,
        public string $checkoutUrl,
    ) {}
}
