<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\WeddingPartnerFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeddingPartner extends Model
{
    /** @use HasFactory<WeddingPartnerFactory> */
    use HasFactory, HasUuids;

    public const TYPE_BRIDE = 'bride';

    public const TYPE_GROOM = 'groom';

    public const TYPE_OTHER = 'other';

    protected $fillable = [
        'wedding_id',
        'partner_type',
        'display_name',
        'nickname',
        'parents',
        'biography',
        'instagram',
        'display_order',
    ];

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }
}
