<?php

declare(strict_types=1);

namespace App\Exceptions\Payments;

use RuntimeException;

/**
 * Raised when a second proof is uploaded while an earlier one is still waiting
 * for an operator decision.
 */
final class PaymentConfirmationPending extends RuntimeException
{
    public static function create(): self
    {
        return new self('Konfirmasi pembayaran sebelumnya masih menunggu verifikasi admin.');
    }
}
