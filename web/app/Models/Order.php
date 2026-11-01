<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentConfirmationStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'public_order_number',
        'user_id',
        'wedding_id',
        'status',
        'currency',
        'subtotal_amount',
        'discount_amount',
        'total_amount',
        'unique_code',
        'payment_expires_at',
        'paid_at',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal_amount' => 'integer',
            'discount_amount' => 'integer',
            'total_amount' => 'integer',
            'unique_code' => 'integer',
            'payment_expires_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function confirmations(): HasMany
    {
        return $this->hasMany(PaymentConfirmation::class);
    }

    public function isPaid(): bool
    {
        return $this->status === OrderStatus::Paid;
    }

    /**
     * The amount the customer must transfer: the package price plus the unique
     * three-digit suffix, so two orders of the same package never share a
     * nominal (docs/PAYMENT_FLOW.md, section 3).
     */
    public function amountDue(): int
    {
        return $this->total_amount + (int) $this->unique_code;
    }

    public function amountDueLabel(): string
    {
        return 'Rp'.number_format($this->amountDue(), 0, ',', '.');
    }

    public function uniqueCodeLabel(): ?string
    {
        return $this->unique_code === null ? null : str_pad((string) $this->unique_code, 3, '0', STR_PAD_LEFT);
    }

    public function hasOpenPaymentWindow(): bool
    {
        return $this->payment_expires_at === null || $this->payment_expires_at->isFuture();
    }

    public function paymentWindowClosed(): bool
    {
        return ! $this->hasOpenPaymentWindow();
    }

    public function latestConfirmation(): ?PaymentConfirmation
    {
        return $this->confirmations->sortByDesc('created_at')->first();
    }

    public function hasPendingConfirmation(): bool
    {
        return $this->confirmations->contains(
            static fn (PaymentConfirmation $confirmation): bool => $confirmation->status === PaymentConfirmationStatus::Submitted,
        );
    }

    /**
     * Pending orders whose window has closed read as expired even though the
     * stored status stays pending_payment, so the state can be reopened with a
     * fresh code without rewriting the order history.
     */
    public function displayStatusLabel(): string
    {
        if ($this->status === OrderStatus::PendingPayment && $this->paymentWindowClosed()) {
            return 'Kedaluwarsa';
        }

        return $this->status->label();
    }

    public function totalLabel(): string
    {
        return $this->currency.' '.number_format($this->total_amount, 0, ',', '.');
    }
}
