<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MediaStatus;
use Database\Factories\MediaAssetFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaAsset extends Model
{
    /** @use HasFactory<MediaAssetFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'wedding_id',
        'album_id',
        'uploaded_by_user_id',
        'object_key',
        'preview_object_key',
        'thumbnail_object_key',
        'original_filename',
        'mime_type',
        'checksum_sha256',
        'byte_size',
        'width',
        'height',
        'status',
        'deleted_at',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status' => MediaStatus::class,
            'byte_size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    public function album(): BelongsTo
    {
        return $this->belongsTo(MediaAlbum::class, 'album_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    /**
     * @param  Builder<MediaAsset>  $query
     * @return Builder<MediaAsset>
     */
    public function scopeReady(Builder $query): Builder
    {
        return $query->where('status', MediaStatus::Ready);
    }

    /**
     * Rows that still count against a quota: everything except fully deleted
     * assets.
     *
     * @param  Builder<MediaAsset>  $query
     * @return Builder<MediaAsset>
     */
    public function scopeCountable(Builder $query): Builder
    {
        return $query->where('status', '!=', MediaStatus::Deleted);
    }

    public function isReady(): bool
    {
        return $this->status->isPubliclyVisible();
    }
}
