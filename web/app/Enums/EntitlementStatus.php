<?php

declare(strict_types=1);

namespace App\Enums;

enum EntitlementStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Expiring = 'expiring';
    case Expired = 'expired';
    case Revoked = 'revoked';
}
