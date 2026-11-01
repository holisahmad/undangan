<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentEventStatus;
use App\Models\PaymentEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PaymentEvent>
 */
class PaymentEventFactory extends Factory
{
    protected $model = PaymentEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider' => 'fake',
            'provider_event_id' => 'evt_'.Str::lower(Str::random(16)),
            'event_type' => 'payment.succeeded',
            'received_at' => now(),
            'processing_status' => PaymentEventStatus::Received,
            'payload' => null,
            'processed_at' => null,
        ];
    }
}
