<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AlbumVisibility;
use Database\Factories\MediaAlbumFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MediaAlbum extends Model
{
    /** @use HasFactory<MediaAlbumFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'wedding_id',
        'title',
        'visibility',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'visibility' => AlbumVisibility::class,
        ];
    }

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(MediaAsset::class, 'album_id');
    }

    public function isPublic(): bool
    {
        return $this->visibility === AlbumVisibility::Public;
    }
}
