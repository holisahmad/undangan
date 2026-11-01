<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Aggregate funnel event. There is intentionally no user id, IP, or user agent:
 * the funnel answers "how many", never "who" (docs/PRIVACY_AND_RETENTION.md).
 */
class AnalyticsEvent extends Model
{
    public const LANDING_VIEWED = 'landing_viewed';

    public const LEAD_CAPTURED = 'lead_captured';

    public const REGISTER_COMPLETED = 'register_completed';

    public const INVITATION_PUBLISHED = 'invitation_published';

    public const INVITATION_VIEWED = 'invitation_viewed';

    public const RSVP_SUBMITTED = 'rsvp_submitted';

    public const EVENTS = [
        self::LANDING_VIEWED,
        self::LEAD_CAPTURED,
        self::REGISTER_COMPLETED,
        self::INVITATION_PUBLISHED,
        self::INVITATION_VIEWED,
        self::RSVP_SUBMITTED,
    ];

    public $timestamps = false;

    protected $fillable = [
        'name',
        'wedding_id',
        'properties',
        'occurred_at',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'occurred_at' => 'datetime',
        ];
    }
}
