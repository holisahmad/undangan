<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

/**
 * Orders are personal: only the buyer may pay, and only the buyer (or an admin
 * for support) may read them.
 */
final class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $this->owns($user, $order) || $user->isAdmin();
    }

    public function pay(User $user, Order $order): bool
    {
        return $this->owns($user, $order);
    }

    private function owns(User $user, Order $order): bool
    {
        return (string) $order->user_id === (string) $user->getKey();
    }
}
