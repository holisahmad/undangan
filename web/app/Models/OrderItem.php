<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'order_id',
        'plan_id',
        'quantity',
        'unit_price_amount',
        'total_amount',
        'configuration_snapshot',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_amount' => 'integer',
            'total_amount' => 'integer',
            'configuration_snapshot' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Duration granted to the entitlement created from this item. Prefers the
     * frozen snapshot so a later catalogue edit cannot change past purchases.
     */
    public function grantedMonths(): int
    {
        $months = $this->configuration_snapshot['duration_months'] ?? null;

        if (is_int($months) && $months > 0) {
            return $months;
        }

        return (int) ($this->plan?->duration_months
            ?? config('platform.invitation.default_active_months', 12));
    }
}
