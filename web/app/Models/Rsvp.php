<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RsvpStatus;
use Database\Factories\RsvpFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rsvp extends Model
{
    /** @use HasFactory<RsvpFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'guest_id',
        'status',
        'party_size',
        'message',
        'show_message',
        'submitted_at',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status' => RsvpStatus::class,
            'party_size' => 'integer',
            'show_message' => 'boolean',
            'submitted_at' => 'datetime',
        ];
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }
}
