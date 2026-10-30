<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\InvitationDraftFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationDraft extends Model
{
    /** @use HasFactory<InvitationDraftFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'wedding_id',
        'template_version_id',
        'content_json',
        'revision',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'content_json' => 'array',
            'revision' => 'integer',
        ];
    }

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(TemplateVersion::class);
    }
}
