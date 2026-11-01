<?php

declare(strict_types=1);

namespace App\Actions\Entitlements;

use App\Enums\EntitlementStatus;
use App\Enums\EntitlementType;
use App\Models\OrderItem;
use App\Models\ServiceEntitlement;
use App\Models\User;
use App\Models\Wedding;
use App\Support\Audit\AuditLogger;
use Carbon\CarbonImmutable;

/**
 * Grants an invitation entitlement.
 *
 * With an order item the grant is the outcome of a verified payment and the
 * duration comes from the frozen order snapshot; an existing active period is
 * extended instead of being overwritten, so transaction history stays intact
 * (docs/PAYMENT_FLOW.md, section 5). Without an order item it is the manual
 * operator grant used for support and development, and it is always audited.
 */
final class GrantInvitationEntitlement
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(
        Wedding $wedding,
        ?User $actor = null,
        ?int $months = null,
        ?OrderItem $orderItem = null,
    ): ServiceEntitlement {
        $months = $orderItem?->grantedMonths()
            ?? $months
            ?? (int) config('platform.invitation.default_active_months', 12);

        $startsAt = $orderItem === null ? CarbonImmutable::now() : $this->nextStartFor($wedding);

        $limits = ['months' => $months];
        $limits['source'] = $orderItem === null ? 'manual_grant' : 'order';

        if ($orderItem !== null) {
            $limits['order_item_id'] = $orderItem->getKey();
            $limits['plan_code'] = $orderItem->configuration_snapshot['plan_code'] ?? null;
            // Freeze the package quotas with the grant so later catalogue
            // edits cannot inflate an existing entitlement.
            $limits['features'] = $orderItem->configuration_snapshot['features'] ?? null;
        }

        $entitlement = ServiceEntitlement::create([
            'wedding_id' => $wedding->getKey(),
            'order_item_id' => $orderItem?->getKey(),
            'entitlement_type' => EntitlementType::Invitation,
            'starts_at' => $startsAt,
            'expires_at' => $startsAt->addMonths($months),
            'status' => EntitlementStatus::Active,
            'limits_json' => $limits,
        ]);

        $this->audit->log(
            action: 'entitlement.granted',
            resourceType: 'wedding',
            resourceId: $wedding->getKey(),
            actor: $actor,
            metadata: [
                'entitlement_id' => $entitlement->getKey(),
                'months' => $months,
                'starts_at' => $entitlement->starts_at?->toIso8601String(),
                'expires_at' => $entitlement->expires_at?->toIso8601String(),
                'source' => $limits['source'],
                'order_item_id' => $orderItem?->getKey(),
            ],
        );

        return $entitlement;
    }

    /**
     * Renewals start when the current period ends; early purchases extend the
     * running period instead of shortening it.
     */
    private function nextStartFor(Wedding $wedding): CarbonImmutable
    {
        $now = CarbonImmutable::now();

        $latestExpiry = ServiceEntitlement::query()
            ->where('wedding_id', $wedding->getKey())
            ->where('entitlement_type', EntitlementType::Invitation->value)
            ->where('status', EntitlementStatus::Active->value)
            ->where('expires_at', '>', $now)
            ->max('expires_at');

        if ($latestExpiry === null) {
            return $now;
        }

        $expiry = CarbonImmutable::parse($latestExpiry);

        return $expiry->greaterThan($now) ? $expiry : $now;
    }
}
