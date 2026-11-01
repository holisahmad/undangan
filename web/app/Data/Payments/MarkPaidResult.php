<?php

declare(strict_types=1);

namespace App\Data\Payments;

use App\Models\ServiceEntitlement;

/**
 * Outcome of settling an order. `duplicate` means the order was already paid,
 * so no new entitlement and no second confirmation email were produced.
 *
 * `entitlements` holds one entry per order item, because the settlement engine
 * is item-generic even though the catalogue currently sells a single item.
 */
final readonly class MarkPaidResult
{
    /**
     * @param  list<ServiceEntitlement>  $entitlements
     */
    public function __construct(
        public bool $newlyPaid,
        public array $entitlements = [],
        public bool $duplicate = false,
    ) {}

    /**
     * Keys of the granted entitlements, in order-item order.
     *
     * @return list<array-key>
     */
    public function entitlementIds(): array
    {
        return array_map(
            static fn (ServiceEntitlement $entitlement) => $entitlement->getKey(),
            $this->entitlements,
        );
    }
}
