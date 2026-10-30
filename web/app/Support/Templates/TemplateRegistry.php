<?php

declare(strict_types=1);

namespace App\Support\Templates;

use InvalidArgumentException;

/**
 * Maps a template version's renderer_key to code that is shipped with the
 * application. Template configuration must never execute uploaded code
 * (docs/DATABASE_SCHEMA.md, section 4).
 */
final class TemplateRegistry
{
    /**
     * @var array<string, string>
     */
    private const RENDERERS = [
        'classic-ivory.v1' => 'invitations.templates.classic-ivory',
    ];

    public function viewFor(string $rendererKey): string
    {
        return self::RENDERERS[$rendererKey]
            ?? throw new InvalidArgumentException("Unknown template renderer [{$rendererKey}].");
    }

    public function supports(string $rendererKey): bool
    {
        return isset(self::RENDERERS[$rendererKey]);
    }

    /**
     * @return list<string>
     */
    public function rendererKeys(): array
    {
        return array_keys(self::RENDERERS);
    }
}
