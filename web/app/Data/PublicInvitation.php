<?php

declare(strict_types=1);

namespace App\Data;

/**
 * Public invitation read model.
 *
 * Only fields listed here are exposed to guests. Access is always granted by
 * an explicit mapping instead of passing the stored snapshot through, so new
 * internal fields cannot leak by accident (docs/PRD.md, section 7.3).
 */
final readonly class PublicInvitation
{
    /**
     * @param  list<array<string, mixed>>  $couple
     * @param  list<array<string, mixed>>  $events
     * @param  array<string, mixed>  $content
     */
    public function __construct(
        public string $slug,
        public string $templateSlug,
        public string $templateName,
        public string $rendererKey,
        public array $couple,
        public array $events,
        public array $content,
        public string $publishedAt,
        public ?string $expiresAt,
    ) {}

    /**
     * @param  array<string, mixed>  $snapshot
     */
    public static function fromSnapshot(string $slug, array $snapshot): self
    {
        $template = is_array($snapshot['template'] ?? null) ? $snapshot['template'] : [];
        $content = is_array($snapshot['content'] ?? null) ? $snapshot['content'] : [];

        return new self(
            slug: $slug,
            templateSlug: (string) ($template['slug'] ?? ''),
            templateName: (string) ($template['name'] ?? ''),
            rendererKey: (string) ($template['renderer_key'] ?? ''),
            couple: self::couple($snapshot['couple'] ?? []),
            events: self::events($snapshot['events'] ?? []),
            content: [
                'cover' => $content['cover'] ?? [],
                'story' => $content['story'] ?? null,
                'features' => $content['features'] ?? [],
            ],
            publishedAt: (string) ($snapshot['published_at'] ?? ''),
            expiresAt: isset($snapshot['expires_at']) ? (string) $snapshot['expires_at'] : null,
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function couple(mixed $couple): array
    {
        if (! is_array($couple)) {
            return [];
        }

        $allowed = ['type', 'display_name', 'nickname', 'parents', 'biography', 'instagram'];

        return array_values(array_map(
            static fn (array $partner): array => array_intersect_key($partner, array_flip($allowed)),
            array_filter($couple, 'is_array'),
        ));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function events(mixed $events): array
    {
        if (! is_array($events)) {
            return [];
        }

        $allowed = ['type', 'title', 'starts_at', 'ends_at', 'starts_label', 'ends_label', 'timezone', 'venue_name', 'address', 'map_url'];

        return array_values(array_map(
            static fn (array $event): array => array_intersect_key($event, array_flip($allowed)),
            array_filter($events, 'is_array'),
        ));
    }

    /**
     * Display name of the couple, e.g. "Ayu & Bima".
     */
    public function coupleHeadline(): string
    {
        $names = array_values(array_filter(array_map(
            static fn (array $partner): string => trim((string) ($partner['nickname'] ?? $partner['display_name'] ?? '')),
            $this->couple,
        )));

        return implode(' & ', $names);
    }
}
