<?php

declare(strict_types=1);

namespace App\Exceptions\Payments;

use RuntimeException;

final class InvalidPaymentSignature extends RuntimeException
{
    public static function for(string $provider): self
    {
        return new self("Signature webhook [{$provider}] tidak valid.");
    }
}
