<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Order lifecycle (docs/PAYMENT_FLOW.md, section 2). Transitions are only ever
 * performed by server-side actions after verified provider notifications.
 */
enum OrderStatus: string
{
    case Draft = 'draft';
    case PendingPayment = 'pending_payment';
    case Paid = 'paid';
    case PaymentFailed = 'payment_failed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::PendingPayment => 'Menunggu pembayaran',
            self::Paid => 'Dibayar',
            self::PaymentFailed => 'Pembayaran gagal',
            self::Cancelled => 'Dibatalkan',
            self::Refunded => 'Dikembalikan',
        };
    }
}
