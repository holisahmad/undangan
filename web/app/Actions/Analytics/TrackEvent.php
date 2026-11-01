<?php

declare(strict_types=1);

namespace App\Actions\Analytics;

use App\Models\AnalyticsEvent;
use App\Models\Wedding;
use Throwable;

/**
 * Records one aggregate funnel event. Recording never throws: a metrics table
 * problem must not break a page, a payment, or an RSVP.
 */
final class TrackEvent
{
    /**
     * @param  array<string, scalar|null>  $properties
     */
    public function handle(string $name, ?Wedding $wedding = null, array $properties = []): void
    {
        try {
            AnalyticsEvent::create([
                'name' => $name,
                'wedding_id' => $wedding?->getKey(),
                'properties' => $properties === [] ? null : $properties,
                'occurred_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
