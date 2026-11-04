<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AlbumVisibility;
use App\Enums\WeddingLifecycleStatus;
use Database\Factories\WeddingFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Wedding extends Model
{
    /** @use HasFactory<WeddingFactory> */
    use HasFactory, HasUuids;

    public const PHOTO_DISPLAY_PREWEDDING = 'prewedding';

    public const PHOTO_DISPLAY_MONOGRAM = 'monogram';

    protected $fillable = [
        'owner_user_id',
        'slug',
        'internal_title',
        'photo_display',
        'cover_media_asset_id',
        'lifecycle_status',
        'showcase_enabled',
        'showcase_enabled_at',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'lifecycle_status' => WeddingLifecycleStatus::class,
            'showcase_enabled' => 'boolean',
            'showcase_enabled_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function partners(): HasMany
    {
        return $this->hasMany(WeddingPartner::class)->orderBy('display_order');
    }

    public function events(): HasMany
    {
        return $this->hasMany(WeddingEvent::class)->orderBy('starts_at');
    }

    public function draft(): HasOne
    {
        return $this->hasOne(InvitationDraft::class);
    }

    public function publications(): HasMany
    {
        return $this->hasMany(InvitationPublication::class);
    }

    /**
     * The publication currently served to guests: newest version that has not
     * been unpublished and has not passed its expiry.
     */
    public function activePublication(): HasOne
    {
        return $this->hasOne(InvitationPublication::class)
            ->whereNull('unpublished_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latestOfMany('version_number');
    }

    public function latestPublication(): HasOne
    {
        return $this->hasOne(InvitationPublication::class)->latestOfMany('version_number');
    }

    public function entitlements(): HasMany
    {
        return $this->hasMany(ServiceEntitlement::class);
    }

    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class);
    }

    public function testimonial(): HasOne
    {
        return $this->hasOne(Testimonial::class);
    }

    public function guestGroups(): HasMany
    {
        return $this->hasMany(GuestGroup::class);
    }

    public function mediaAssets(): HasMany
    {
        return $this->hasMany(MediaAsset::class);
    }

    public function mediaAlbums(): HasMany
    {
        return $this->hasMany(MediaAlbum::class);
    }

    /**
     * The photo the couple picked for the opening section of the invitation.
     */
    public function coverPhoto(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'cover_media_asset_id');
    }

    /**
     * A couple without pre-wedding photos can ask for a monogram instead; the
     * invitation then never shows imagery at all.
     */
    public function showsPreWeddingPhotos(): bool
    {
        return $this->photo_display !== self::PHOTO_DISPLAY_MONOGRAM;
    }

    public function archives(): HasMany
    {
        return $this->hasMany(ArchiveJob::class);
    }

    /**
     * True when at least one public album holds a ready photo. Used by the
     * invitation templates to decide whether a gallery link is useful.
     */
    public function hasPublicGallery(): bool
    {
        return $this->mediaAlbums()
            ->where('visibility', AlbumVisibility::Public)
            ->whereHas('assets', static fn ($query) => $query->ready())
            ->exists();
    }

    public function activeInvitationEntitlement(): ?ServiceEntitlement
    {
        return $this->entitlements()
            ->where('entitlement_type', 'invitation')
            ->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('expires_at')
            ->first();
    }
}
