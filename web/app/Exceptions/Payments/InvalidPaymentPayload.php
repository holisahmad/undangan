<?php

declare(strict_types=1);

namespace App\Exceptions\Payments;

use RuntimeException;

final class InvalidPaymentPayload extends RuntimeException
{
    public static function reason(string $reason): self
    {
        return new self($reason);
    }
}
