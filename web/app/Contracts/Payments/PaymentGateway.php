<?php

declare(strict_types=1);

namespace App\Contracts\Payments;

use App\Data\Payments\PaymentInitiation;
use App\Data\Payments\PaymentNotification;
use App\Models\Order;
use App\Models\Payment;

/**
 * Adapter boundary for a payment provider. Implementations must never trust the
 * browser: the webhook payload is verified against a signature and the order is
 * re-checked server-side (docs/PAYMENT_FLOW.md, section 4).
 */
interface PaymentGateway
{
    public function name(): string;

    /**
     * Whether the gateway may be used at all right now. The registry refuses
     * unavailable providers, so a disabled or production-excluded gateway also
     * stops answering its webhook endpoint instead of only its checkout.
     */
    public function isEnabled(): bool;

    /**
     * Starts a provider transaction for an order that is ready to be paid.
     */
    public function createPayment(Order $order, Payment $payment): PaymentInitiation;

    /**
     * Verifies the raw request body against the provider signature header.
     */
    public function verifySignature(string $rawPayload, ?string $signature): bool;

    /**
     * Header carrying the signature on webhook deliveries.
     */
    public function signatureHeader(): string;

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws \InvalidArgumentException when required fields are missing
     */
    public function parseNotification(array $payload): PaymentNotification;
}
