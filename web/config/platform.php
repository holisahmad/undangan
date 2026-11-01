<?php

declare(strict_types=1);
use App\Payments\FakePaymentGateway;

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

    /*
    |--------------------------------------------------------------------------
    | Guests and RSVP
    |--------------------------------------------------------------------------
    |
    | Personal links are bearer secrets, so both the personalised page and the
    | RSVP submission are rate limited per IP (docs/SECURITY.md, section 9).
    |
    */
    'guests' => [
        'rate_limit_per_minute' => (int) env('GUEST_INVITATION_RATE_LIMIT', 60),
        'rsvp_rate_limit_per_minute' => (int) env('GUEST_RSVP_RATE_LIMIT', 10),
        'max_party_size' => (int) env('GUEST_MAX_PARTY_SIZE', 20),
        'import_max_rows' => (int) env('GUEST_IMPORT_MAX_ROWS', 500),
        // RSVP reminders are transactional email to guests who supplied an
        // address; the cooldown keeps scheduled runs from nagging.
        'reminder_cooldown_days' => (int) env('GUEST_REMINDER_COOLDOWN_DAYS', 7),
        'reminder_batch_limit' => (int) env('GUEST_REMINDER_BATCH_LIMIT', 200),
    ],

    /*
    |--------------------------------------------------------------------------
    | Marketing surface
    |--------------------------------------------------------------------------
    |
    | The contact form and the public showcase are unauthenticated, so both are
    | capped per IP. Leads store first-touch attribution only.
    |
    */
    'marketing' => [
        'lead_rate_limit_per_minute' => (int) env('MARKETING_LEAD_RATE_LIMIT', 6),
        'showcase_limit' => (int) env('MARKETING_SHOWCASE_LIMIT', 24),
        'testimonial_limit' => (int) env('MARKETING_TESTIMONIAL_LIMIT', 6),
        // Theme catalogue (/tema). Nine cards keep three full rows on a desktop
        // and the page is paginated rather than infinite so it stays linkable.
        'catalog_per_page' => (int) env('MARKETING_CATALOG_PER_PAGE', 9),
        // The landing page only shows a curated strip and links to the catalogue
        // for the rest, so it never grows with the template count.
        'landing_template_limit' => (int) env('MARKETING_LANDING_TEMPLATE_LIMIT', 6),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security hardening
    |--------------------------------------------------------------------------
    |
    | The CSP runs report-only until the collected violations are reviewed.
    | Enforcing admin 2FA is opt-in per deployment so a fresh install cannot
    | lock itself out before the first operator enrols an authenticator.
    |
    */
    'security' => [
        'require_admin_two_factor' => filter_var(env('ADMIN_REQUIRE_TWO_FACTOR', false), FILTER_VALIDATE_BOOLEAN),
        'csp_report_only' => filter_var(env('SECURITY_CSP_REPORT_ONLY', true), FILTER_VALIDATE_BOOLEAN),
        'csp_policy' => env('SECURITY_CSP_POLICY', implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "img-src 'self' data: https://ui-avatars.com",
            "font-src 'self' data: https://fonts.gstatic.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "script-src 'self'",
            "media-src 'self' https://assets.inviee.id",
            "connect-src 'self'",
        ])),
        'register_rate_limit_per_minute' => (int) env('REGISTER_RATE_LIMIT', 5),
        'two_factor_rate_limit_per_minute' => (int) env('TWO_FACTOR_RATE_LIMIT', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Gallery, archive and media storage
    |--------------------------------------------------------------------------
    |
    | The disk name is a deployment concern: local by default, S3-compatible in
    | production once the adapter package is installed. Limits are per-file
    | guardrails, while photo count and storage bytes come from the entitlement
    | snapshot (docs/MEDIA_STORAGE.md, sections 2-7).
    |
    */
    'media' => [
        'disk' => env('MEDIA_DISK', 'media'),
        'max_files_per_upload' => (int) env('MEDIA_MAX_FILES_PER_UPLOAD', 10),
        'max_file_kb' => (int) env('MEDIA_MAX_FILE_KB', 8192),
        // Ceiling for one request: every photo in the same submit shares it.
        // App\Support\Media\UploadLimits lowers both values to PHP's
        // upload_max_filesize/post_max_size so the form never promises more
        // than the host accepts (docs/MEDIA_STORAGE.md, section 10).
        'max_upload_kb' => (int) env('MEDIA_MAX_UPLOAD_KB', 24576),
        'respect_php_limits' => filter_var(env('MEDIA_RESPECT_PHP_LIMITS', true), FILTER_VALIDATE_BOOLEAN),
        'max_pixels' => (int) env('MEDIA_MAX_PIXELS', 24_000_000),
        'max_dimension' => (int) env('MEDIA_MAX_DIMENSION', 8000),
        'preview_max_width' => (int) env('MEDIA_PREVIEW_MAX_WIDTH', 1280),
        'thumbnail_max_width' => (int) env('MEDIA_THUMBNAIL_MAX_WIDTH', 400),
        'signed_url_ttl_minutes' => (int) env('MEDIA_SIGNED_URL_TTL_MINUTES', 15),
        'archive_retention_days' => (int) env('MEDIA_ARCHIVE_RETENTION_DAYS', 7),
        'archive_max_assets' => (int) env('MEDIA_ARCHIVE_MAX_ASSETS', 200),
        'archive_max_bytes' => (int) env('MEDIA_ARCHIVE_MAX_BYTES', 536_870_912),
        'cleanup_quarantine_hours' => (int) env('MEDIA_CLEANUP_QUARANTINE_HOURS', 24),
        'public_rate_limit_per_minute' => (int) env('MEDIA_PUBLIC_RATE_LIMIT', 30),
        // Optional external scanner (e.g. clamdscan). Empty means MIME and
        // dimension validation only; see docs/MEDIA_STORAGE.md.
        'scan_command' => env('MEDIA_SCAN_COMMAND'),
        'scan_fail_closed' => filter_var(env('MEDIA_SCAN_FAIL_CLOSED', true), FILTER_VALIDATE_BOOLEAN),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payments
    |--------------------------------------------------------------------------
    |
    | The default flow is a manual bank transfer: the customer is quoted a
    | three-digit unique code added to the package price and must confirm the
    | transfer within a limited window. Every account in PAYMENT_BANK_ACCOUNTS is
    | shown on the instruction page (bank|number|holder, separated by semicolons).
    |
    | The "fake" sandbox gateway stays available for tests and local demos; a
    | real provider is added by implementing App\Contracts\Payments\PaymentGateway
    | and registering it in the gateway list (docs/adr/007-orders-and-payments.md).
    | Registration is only the catalogue: PaymentGatewayRegistry refuses any
    | provider whose isEnabled() says no, so a listed gateway can still be closed
    | per environment and per deployment.
    |
    */
    'payments' => [
        'default_gateway' => env('PAYMENT_GATEWAY', 'bank_transfer'),

        'gateways' => [
            'fake' => FakePaymentGateway::class,
        ],

        'bank_transfer' => [
            // Hours the customer has to complete the transfer before the code
            // expires (docs/PAYMENT_FLOW.md, section 3).
            'window_hours' => (int) env('PAYMENT_WINDOW_HOURS', 24),
            'unique_code_min' => 100,
            'unique_code_max' => 999,
            'accounts' => env('PAYMENT_BANK_ACCOUNTS', ''),
            'proof_disk' => env('PAYMENT_PROOF_DISK', 'local'),
            'proof_max_kb' => (int) env('PAYMENT_PROOF_MAX_KB', 4096),
            'proof_mimes' => ['jpg', 'jpeg', 'png', 'pdf'],
        ],

        'fake' => [
            // Simulated checkout and its signed webhook are local/testing
            // conveniences. Both default to off: a deployment that does not ask
            // for the sandbox cannot serve it (docs/DEPLOYMENT.md).
            'enabled' => filter_var(env('PAYMENT_FAKE_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

            // Deliberately no fallback value. A secret committed to the
            // repository is not a secret, so a missing entry disables the
            // sandbox webhook rather than leaving it on a known key.
            'webhook_secret' => env('PAYMENT_FAKE_WEBHOOK_SECRET'),
        ],
    ],
];
