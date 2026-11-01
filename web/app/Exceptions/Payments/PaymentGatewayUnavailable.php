<?php

declare(strict_types=1);

namespace App\Exceptions\Payments;

use RuntimeException;

/**
 * Raised when a gateway is used outside the environment it supports, for
 * example the sandbox gateway in production.
 */
final class PaymentGatewayUnavailable extends RuntimeException
{
    public static function sandboxDisabled(): self
    {
        return new self('Gateway pembayaran simulasi tidak tersedia di lingkungan ini.');
    }
}
