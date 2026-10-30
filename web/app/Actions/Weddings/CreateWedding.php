<?php

declare(strict_types=1);

namespace App\Actions\Weddings;

use App\Enums\WeddingLifecycleStatus;
use App\Models\InvitationDraft;
use App\Models\TemplateVersion;
use App\Models\User;
use App\Models\Wedding;
use App\Support\Templates\TemplateRegistry;
use App\Support\Weddings\WeddingSlug;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class CreateWedding
{
    public function __construct(private readonly TemplateRegistry $registry) {}

    public function handle(User $owner, string $internalTitle, TemplateVersion $templateVersion): Wedding
    {
        if ($templateVersion->status !== 'published' || ! $this->registry->supports($templateVersion->renderer_key)) {
            throw new InvalidArgumentException('Template tidak tersedia untuk undangan baru.');
        }

        return DB::transaction(function () use ($owner, $internalTitle, $templateVersion): Wedding {
            $wedding = Wedding::create([
                'owner_user_id' => $owner->getKey(),
                'slug' => WeddingSlug::generate($internalTitle),
                'internal_title' => $internalTitle,
                'lifecycle_status' => WeddingLifecycleStatus::Draft,
            ]);

            InvitationDraft::create([
                'wedding_id' => $wedding->getKey(),
                'template_version_id' => $templateVersion->getKey(),
                'content_json' => self::defaultContent(),
                'revision' => 1,
            ]);

            return $wedding;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultContent(): array
    {
        return [
            'cover' => [
                'title' => 'The Wedding Of',
                'greeting' => null,
                'quote' => null,
                'quote_source' => null,
            ],
            'story' => null,
            'features' => [
                'show_story' => true,
                'show_events' => true,
                'show_map' => true,
            ],
        ];
    }
}
