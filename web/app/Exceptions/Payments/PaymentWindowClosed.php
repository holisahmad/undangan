<?php

declare(strict_types=1);

namespace App\Exceptions\Payments;

use RuntimeException;

/**
 * Raised when a transfer is confirmed after the payment deadline. The customer
 * can reopen the window with a fresh unique code.
 */
final class PaymentWindowClosed extends RuntimeException
{
    public static function create(): self
    {
        return new self('Batas waktu pembayaran sudah lewat. Buat kode pembayaran baru untuk melanjutkan.');
    }
}
