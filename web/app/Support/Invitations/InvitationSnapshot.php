<?php

declare(strict_types=1);

namespace App\Support\Invitations;

use App\Models\InvitationDraft;
use App\Models\TemplateVersion;
use App\Models\Wedding;
use Carbon\CarbonInterface;

/**
 * Freezes the editable state of a wedding into the payload that will be served
 * to guests. Publishing stores this payload, so later draft edits can never
 * change a live invitation (docs/ARCHITECTURE.md, section 5).
 */
final class InvitationSnapshot
{
    public const FORMAT_VERSION = 1;

    /**
     * @return array<string, mixed>
     */
    public function build(
        Wedding $wedding,
        InvitationDraft $draft,
        TemplateVersion $templateVersion,
        CarbonInterface $publishedAt,
        ?CarbonInterface $expiresAt,
    ): array {
        $wedding->loadMissing(['partners', 'events']);
        $template = $templateVersion->template;

        return [
            'format_version' => self::FORMAT_VERSION,
            'template' => [
                'slug' => $template->slug,
                'name' => $template->name,
                'version' => $templateVersion->version,
                'renderer_key' => $templateVersion->renderer_key,
                'config_schema_version' => $templateVersion->config_schema_version,
            ],
            'couple' => $wedding->partners
                ->map(static fn ($partner): array => [
                    'type' => $partner->partner_type,
                    'display_name' => $partner->display_name,
                    'nickname' => $partner->nickname,
                    'parents' => $partner->parents,
                    'biography' => $partner->biography,
                    'instagram' => $partner->instagram,
                ])
                ->all(),
            'events' => $wedding->events
                ->map(static fn ($event): array => [
                    'type' => $event->event_type,
                    'title' => $event->title,
                    'starts_at' => $event->starts_at?->toIso8601String(),
                    'ends_at' => $event->ends_at?->toIso8601String(),
                    'starts_label' => self::label($event->starts_at, $event->timezone, 'l, d F Y · H:i'),
                    'ends_label' => self::label($event->ends_at, $event->timezone, 'H:i'),
                    'timezone' => $event->timezone,
                    'venue_name' => $event->venue_name,
                    'address' => $event->address,
                    'map_url' => $event->map_url,
                ])
                ->all(),
            'content' => $draft->content_json,
            'published_at' => $publishedAt->toIso8601String(),
            'expires_at' => $expiresAt?->toIso8601String(),
        ];
    }

    private static function label(?CarbonInterface $moment, string $timezone, string $format): ?string
    {
        return $moment
            ?->copy()
            ->setTimezone($timezone)
            ->locale(app()->getLocale())
            ->translatedFormat($format);
    }
}
