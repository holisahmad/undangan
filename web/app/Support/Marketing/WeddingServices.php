<?php

declare(strict_types=1);

namespace App\Support\Marketing;

/**
 * The wedding services that are offered alongside the digital invitation.
 *
 * They are configured, not stored: the platform does not perform them, it
 * coordinates them with partners, so the wording and the number that answers
 * belong in config/site.php next to the contact details. This class is the one
 * place that reads them, so a landing card, the enquiry form, the validation
 * rule and the admin list can never disagree about which services exist.
 */
final class WeddingServices
{
    /**
     * @return list<array{slug: string, name: string, text: string, provider: string}>
     */
    public static function all(): array
    {
        $rows = [];

        foreach ((array) config('site.services', []) as $service) {
            $slug = trim((string) ($service['slug'] ?? ''));
            $name = trim((string) ($service['name'] ?? ''));

            if ($slug === '' || $name === '') {
                // A card without a name is decoration, and a slug without a name
                // cannot be turned back into an answerable enquiry.
                continue;
            }

            $rows[] = [
                'slug' => $slug,
                'name' => $name,
                'text' => trim((string) ($service['text'] ?? '')),
                'provider' => trim((string) ($service['provider'] ?? 'Vendor mitra')),
            ];
        }

        return $rows;
    }

    /**
     * The values the enquiry form is allowed to submit.
     *
     * @return list<string>
     */
    public static function slugs(): array
    {
        return array_column(self::all(), 'slug');
    }

    /**
     * Display name for a stored interest, or null when nothing was chosen. An
     * unknown slug keeps its raw value at the call site rather than inventing a
     * label for a service that was removed from the catalogue.
     */
    public static function label(?string $slug): ?string
    {
        $slug = trim((string) $slug);

        if ($slug === '') {
            return null;
        }

        foreach (self::all() as $service) {
            if ($service['slug'] === $slug) {
                return $service['name'];
            }
        }

        return null;
    }
}
