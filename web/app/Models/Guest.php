<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\GuestFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Guest extends Model
{
    /** @use HasFactory<GuestFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'wedding_id',
        'guest_group_id',
        'display_name',
        'contact_phone',
        'contact_email',
        'rsvp_reminded_at',
        'allowed_party_size',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'allowed_party_size' => 'integer',
            'rsvp_reminded_at' => 'datetime',
        ];
    }

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(GuestGroup::class, 'guest_group_id');
    }

    public function rsvp(): HasOne
    {
        return $this->hasOne(Rsvp::class);
    }

    public function invitationLinks(): HasMany
    {
        return $this->hasMany(GuestInvitationLink::class);
    }

    /**
     * Issuing a link revokes every previous one, so at most one row matches.
     */
    public function activeInvitationLink(): HasOne
    {
        return $this->hasOne(GuestInvitationLink::class)
            ->whereNull('revoked_at')
            ->where(static function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Newest row regardless of state, used to explain why no link is usable.
     * Ordered by creation time because ids are random UUIDs.
     */
    public function latestInvitationLink(): HasOne
    {
        return $this->hasOne(GuestInvitationLink::class)->latestOfMany('created_at');
    }
}
