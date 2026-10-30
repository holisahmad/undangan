<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EntitlementStatus;
use App\Enums\EntitlementType;
use Database\Factories\ServiceEntitlementFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceEntitlement extends Model
{
    /** @use HasFactory<ServiceEntitlementFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'wedding_id',
        'order_item_id',
        'entitlement_type',
        'starts_at',
        'expires_at',
        'limits_json',
        'status',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'entitlement_type' => EntitlementType::class,
            'status' => EntitlementStatus::class,
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'limits_json' => 'array',
        ];
    }

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    public function isActiveAt(\DateTimeInterface $moment): bool
    {
        if ($this->status !== EntitlementStatus::Active) {
            return false;
        }

        if ($this->starts_at->greaterThan($moment)) {
            return false;
        }

        return $this->expires_at === null || $this->expires_at->greaterThan($moment);
    }
}
