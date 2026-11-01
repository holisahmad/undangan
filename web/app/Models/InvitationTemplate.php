<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\InvitationTemplateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvitationTemplate extends Model
{
    /** @use HasFactory<InvitationTemplateFactory> */
    use HasFactory, HasUuids;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ARCHIVED = 'archived';

    /**
     * Package tiers, ordered from cheapest to most expensive. A template states
     * the lowest package it belongs to; a plan states the highest tier it
     * unlocks, so the two are compared by rank (docs/BUSINESS_MODEL.md).
     */
    public const TIER_ESSENTIAL = 'essential';

    public const TIER_SIGNATURE = 'signature';

    public const TIER_PREMIUM = 'premium';

    /**
     * @var array<string, int>
     */
    public const TIER_RANKS = [
        self::TIER_ESSENTIAL => 1,
        self::TIER_SIGNATURE => 2,
        self::TIER_PREMIUM => 3,
    ];

    public const BADGE_NEW = 'new';

    public const BADGE_BEST = 'best';

    protected $fillable = [
        'slug',
        'name',
        'category',
        'tier',
        'badge',
        'sort_order',
        'status',
        'thumbnail_object_key',
        'description',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(TemplateVersion::class, 'template_id');
    }

    public function publishedVersion(): ?TemplateVersion
    {
        return $this->versions()->where('status', 'published')->orderByDesc('version')->first();
    }

    /**
     * @param  Builder<InvitationTemplate>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * @param  Builder<InvitationTemplate>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', self::STATUS_ACTIVE)
            ->whereHas('versions', static fn (Builder $versions): mixed => $versions->where('status', 'published'));
    }

    public function tierRank(): int
    {
        return self::TIER_RANKS[$this->tier ?? self::TIER_ESSENTIAL] ?? self::TIER_RANKS[self::TIER_ESSENTIAL];
    }

    /**
     * Unknown tier values come from hand-edited rows, never from the catalogue;
     * the cheapest tier is the safe reading because it unlocks nothing.
     */
    public function isExclusivelyPremium(): bool
    {
        return $this->tierRank() >= self::TIER_RANKS[self::TIER_PREMIUM];
    }
}
