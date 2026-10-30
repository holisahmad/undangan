<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\WeddingEventFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeddingEvent extends Model
{
    /** @use HasFactory<WeddingEventFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'wedding_id',
        'event_type',
        'title',
        'starts_at',
        'ends_at',
        'timezone',
        'venue_name',
        'address',
        'map_url',
        'display_order',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }
}
