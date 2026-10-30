<?php

declare(strict_types=1);

namespace App\Actions\Entitlements;

use App\Enums\EntitlementStatus;
use App\Enums\EntitlementType;
use App\Models\ServiceEntitlement;
use App\Models\User;
use App\Models\Wedding;
use App\Support\Audit\AuditLogger;
use Carbon\CarbonImmutable;

/**
 * Grants an invitation entitlement without a payment.
 *
 * This exists so the core flow is testable before the order/payment phase
 * ships. It is only reachable through an Artisan command, never through the
 * customer UI, and it is always audited.
 */
final class GrantInvitationEntitlement
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(Wedding $wedding, ?User $actor = null, ?int $months = null): ServiceEntitlement
    {
        $months ??= (int) config('platform.invitation.default_active_months', 12);
        $startsAt = CarbonImmutable::now();

        $entitlement = ServiceEntitlement::create([
            'wedding_id' => $wedding->getKey(),
            'entitlement_type' => EntitlementType::Invitation,
            'starts_at' => $startsAt,
            'expires_at' => $startsAt->addMonths($months),
            'status' => EntitlementStatus::Active,
            'limits_json' => ['source' => 'manual_grant', 'months' => $months],
        ]);

        $this->audit->log(
            action: 'entitlement.granted',
            resourceType: 'wedding',
            resourceId: $wedding->getKey(),
            actor: $actor,
            metadata: [
                'entitlement_id' => $entitlement->getKey(),
                'months' => $months,
                'expires_at' => $entitlement->expires_at?->toIso8601String(),
            ],
        );

        return $entitlement;
    }
}
