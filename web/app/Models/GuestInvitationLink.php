<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Guests\GuestToken;
use Database\Factories\GuestInvitationLinkFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bearer link that personalises the invitation for a single guest.
 *
 * Only a hash is persisted; the raw token is shown once when it is issued and
 * is never logged (docs/DATABASE_SCHEMA.md, section 6).
 */
class GuestInvitationLink extends Model
{
    /** @use HasFactory<GuestInvitationLinkFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'guest_id',
        'token_hash',
        'expires_at',
        'revoked_at',
        'last_used_at',
        'view_count',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_used_at' => 'datetime',
            'view_count' => 'integer',
        ];
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    /**
     * Resolve a raw token to its link without ever storing the token itself.
     */
    public static function resolve(?string $rawToken): ?self
    {
        if ($rawToken === null || trim($rawToken) === '') {
            return null;
        }

        return self::query()
            ->where('token_hash', GuestToken::hash($rawToken))
            ->whereNull('revoked_at')
            ->where(static function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return ! $this->isRevoked() && ! $this->isExpired();
    }

    /**
     * Cheap visit counter for the owner's dashboard. It touches usage columns
     * only, so listing statistics never look like guest edits.
     */
    public function registerView(): void
    {
        $this->newQuery()
            ->whereKey($this->getKey())
            ->increment('view_count', 1, ['last_used_at' => now()]);
    }
}
