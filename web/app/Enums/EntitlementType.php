<?php

declare(strict_types=1);

namespace App\Enums;

enum EntitlementType: string
{
    case Invitation = 'invitation';
    case Gallery = 'gallery';
    case Archive = 'archive';
    case Storage = 'storage';
    case Feature = 'feature';
}
