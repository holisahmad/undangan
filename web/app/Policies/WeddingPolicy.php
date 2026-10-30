<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Wedding;

/**
 * Every wedding resource is checked per request. A UUID in a URL is never
 * treated as authorisation (docs/SECURITY.md, section 3).
 */
final class WeddingPolicy
{
    public function view(User $user, Wedding $wedding): bool
    {
        return $this->owns($user, $wedding) || $user->isAdmin();
    }

    public function update(User $user, Wedding $wedding): bool
    {
        return $this->owns($user, $wedding);
    }

    public function publish(User $user, Wedding $wedding): bool
    {
        return $this->owns($user, $wedding);
    }

    public function unpublish(User $user, Wedding $wedding): bool
    {
        return $this->owns($user, $wedding);
    }

    private function owns(User $user, Wedding $wedding): bool
    {
        return (string) $wedding->owner_user_id === (string) $user->getKey();
    }
}
