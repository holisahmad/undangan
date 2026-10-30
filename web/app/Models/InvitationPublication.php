<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\InvitationPublicationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationPublication extends Model
{
    /** @use HasFactory<InvitationPublicationFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'wedding_id',
        'version_number',
        'published_snapshot',
        'published_by',
        'published_at',
        'unpublished_at',
        'expires_at',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'published_snapshot' => 'array',
            'published_at' => 'datetime',
            'unpublished_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function isLive(): bool
    {
        return $this->unpublished_at === null
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function hasExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
