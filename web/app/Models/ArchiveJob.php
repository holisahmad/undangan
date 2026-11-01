<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ArchiveStatus;
use Database\Factories\ArchiveJobFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchiveJob extends Model
{
    /** @use HasFactory<ArchiveJobFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'wedding_id',
        'requested_by_user_id',
        'status',
        'asset_count',
        'byte_size',
        'object_key',
        'manifest_json',
        'error_message',
        'expires_at',
        'started_at',
        'finished_at',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status' => ArchiveStatus::class,
            'asset_count' => 'integer',
            'byte_size' => 'integer',
            'manifest_json' => 'array',
            'expires_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function isDownloadable(): bool
    {
        return $this->status === ArchiveStatus::Ready
            && $this->object_key !== null
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function hasExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
