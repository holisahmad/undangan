<?php

declare(strict_types=1);

namespace App\Exceptions\Payments;

use RuntimeException;

final class UnknownPaymentProvider extends RuntimeException
{
    public static function named(string $provider): self
    {
        return new self("Penyedia pembayaran [{$provider}] tidak dikenal.");
    }
}
