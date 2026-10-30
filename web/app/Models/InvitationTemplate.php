<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\InvitationTemplateFactory;
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

    protected $fillable = [
        'slug',
        'name',
        'category',
        'status',
        'thumbnail_object_key',
        'description',
    ];

    public function versions(): HasMany
    {
        return $this->hasMany(TemplateVersion::class, 'template_id');
    }

    public function publishedVersion(): ?TemplateVersion
    {
        return $this->versions()->where('status', 'published')->orderByDesc('version')->first();
    }
}
