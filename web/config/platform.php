<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Invitation lifecycle
    |--------------------------------------------------------------------------
    |
    | Durations are product hypotheses (docs/PRIVACY_AND_RETENTION.md, section 3)
    | and must remain configurable instead of being hardcoded at call sites.
    |
    */
    'invitation' => [
        'default_active_months' => (int) env('INVITATION_DEFAULT_ACTIVE_MONTHS', 12),
    ],

    /*
    |--------------------------------------------------------------------------
    | Public invitation access
    |--------------------------------------------------------------------------
    */
    'public' => [
        'rate_limit_per_minute' => (int) env('PUBLIC_INVITATION_RATE_LIMIT', 60),
    ],
];
