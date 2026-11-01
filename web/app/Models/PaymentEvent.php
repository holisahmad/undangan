<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentEventStatus;
use Database\Factories\PaymentEventFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Every webhook delivery is recorded before it is acted upon. The unique
 * (provider, provider_event_id) pair is what makes processing idempotent.
 */
class PaymentEvent extends Model
{
    /** @use HasFactory<PaymentEventFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'provider',
        'provider_event_id',
        'event_type',
        'received_at',
        'processing_status',
        'payload',
        'processed_at',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'processing_status' => PaymentEventStatus::class,
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function markProcessed(): void
    {
        $this->forceFill([
            'processing_status' => PaymentEventStatus::Processed,
            'processed_at' => now(),
        ])->save();
    }

    public function markIgnored(): void
    {
        $this->forceFill([
            'processing_status' => PaymentEventStatus::Ignored,
            'processed_at' => now(),
        ])->save();
    }

    public function markFailed(): void
    {
        $this->forceFill([
            'processing_status' => PaymentEventStatus::Failed,
            'processed_at' => now(),
        ])->save();
    }
}
