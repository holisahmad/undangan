<?php

declare(strict_types=1);

namespace App\Data;

use Carbon\CarbonImmutable;
use Throwable;

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
                'gift' => self::gift($content['gift'] ?? null),
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
     * Bank accounts are guest-facing text, so every field is re-cast here and
     * rows without a number are dropped: an empty editor slot must never render
     * a blank gift card.
     *
     * @return array<string, mixed>
     */
    private static function gift(mixed $gift): array
    {
        if (! is_array($gift)) {
            return ['enabled' => false, 'note' => null, 'address' => null, 'accounts' => []];
        }

        $accounts = [];

        foreach (is_array($gift['accounts'] ?? null) ? $gift['accounts'] : [] as $account) {
            if (! is_array($account)) {
                continue;
            }

            $number = trim((string) ($account['number'] ?? ''));

            if ($number === '') {
                continue;
            }

            $accounts[] = [
                'bank' => trim((string) ($account['bank'] ?? '')),
                'number' => $number,
                'holder' => trim((string) ($account['holder'] ?? '')),
            ];
        }

        $address = trim((string) ($gift['address'] ?? ''));
        $note = trim((string) ($gift['note'] ?? ''));

        return [
            'enabled' => (bool) ($gift['enabled'] ?? true) && ($accounts !== [] || $address !== ''),
            'note' => $note === '' ? null : $note,
            'address' => $address === '' ? null : $address,
            'accounts' => $accounts,
        ];
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

    public function giftIsVisible(): bool
    {
        $gift = $this->content['gift'] ?? [];

        return (bool) ($gift['enabled'] ?? false)
            && (($gift['accounts'] ?? []) !== [] || ($gift['address'] ?? null) !== null);
    }

    /**
     * @return list<array{bank: string, number: string, holder: string}>
     */
    public function giftAccounts(): array
    {
        $accounts = $this->content['gift']['accounts'] ?? [];

        return is_array($accounts) ? array_values($accounts) : [];
    }

    /**
     * The ceremony that guests are counting down to: the earliest event that has
     * not started yet, or null when every event is in the past.
     *
     * @return array<string, mixed>|null
     */
    public function nextEvent(): ?array
    {
        $next = null;
        $nextMoment = null;

        foreach ($this->events as $event) {
            $moment = self::moment($event['starts_at'] ?? null);

            if ($moment === null || $moment->isPast()) {
                continue;
            }

            if ($nextMoment === null || $moment->lessThan($nextMoment)) {
                $next = $event;
                $nextMoment = $moment;
            }
        }

        return $next;
    }

    /**
     * Remaining time for the countdown block. Values are computed server-side so
     * a guest without JavaScript still reads a correct figure.
     *
     * @return array{target_iso: string, label: string, days: int, hours: int, minutes: int, seconds: int, total_seconds: int}|null
     */
    public function countdown(): ?array
    {
        $next = $this->nextEvent();
        $target = self::moment($next['starts_at'] ?? null);

        if ($next === null || $target === null) {
            return null;
        }

        $remaining = (int) CarbonImmutable::now()->diffInSeconds($target, false);

        if ($remaining <= 0) {
            return null;
        }

        return [
            'target_iso' => $target->toIso8601String(),
            'label' => (string) ($next['title'] ?? 'Acara'),
            'days' => intdiv($remaining, 86400),
            'hours' => intdiv($remaining % 86400, 3600),
            'minutes' => intdiv($remaining % 3600, 60),
            'seconds' => $remaining % 60,
            'total_seconds' => $remaining,
        ];
    }

    private static function moment(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
