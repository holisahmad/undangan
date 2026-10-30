<?php

declare(strict_types=1);

namespace App\Support\Templates;

use Illuminate\Support\Facades\Validator;

/**
 * Validates the presentation part of an invitation draft against the content
 * schema declared by the template's config_schema_version.
 *
 * Couple details and events are relational data (wedding_partners,
 * wedding_events), so they are not duplicated here. This payload only stores
 * what is template specific.
 */
final class InvitationContentValidator
{
    public const SCHEMA_VERSION = '1';

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    public function validate(array $content, string $schemaVersion = self::SCHEMA_VERSION): array
    {
        return Validator::make($content, $this->rulesFor($schemaVersion))->validate();
    }

    /**
     * @return array<string, list<string>>
     */
    private function rulesFor(string $schemaVersion): array
    {
        // Unknown schema versions are rejected instead of silently accepted so a
        // broken template version cannot corrupt stored drafts.
        if ($schemaVersion !== self::SCHEMA_VERSION) {
            return ['content' => ['prohibited']];
        }

        return [
            'cover' => ['required', 'array'],
            'cover.title' => ['required', 'string', 'max:120'],
            'cover.greeting' => ['nullable', 'string', 'max:160'],
            'cover.quote' => ['nullable', 'string', 'max:300'],
            'cover.quote_source' => ['nullable', 'string', 'max:120'],

            'story' => ['nullable', 'array'],
            'story.heading' => ['nullable', 'string', 'max:80'],
            'story.body' => ['nullable', 'string', 'max:2000'],

            'features' => ['required', 'array'],
            'features.show_story' => ['required', 'boolean'],
            'features.show_events' => ['required', 'boolean'],
            'features.show_map' => ['required', 'boolean'],
        ];
    }
}
