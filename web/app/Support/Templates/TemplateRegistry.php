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
        'modern-editorial.v1' => 'invitations.templates.modern-editorial',
        'garden-botanical.v1' => 'invitations.templates.garden-botanical',
        'emerald-gilded.v1' => 'invitations.templates.emerald-gilded',
        'sage-keepsake.v1' => 'invitations.templates.sage-keepsake',
        'batik-pusaka.v1' => 'invitations.templates.batik-pusaka',
        'midnight-celestial.v1' => 'invitations.templates.midnight-celestial',
        'terracotta-sunset.v1' => 'invitations.templates.terracotta-sunset',
        'glasshouse-minimal.v1' => 'invitations.templates.glasshouse-minimal',
        'pesisir-senja.v1' => 'invitations.templates.pesisir-senja',
        'songket-empress.v1' => 'invitations.templates.songket-empress',
        'velvet-royal.v1' => 'invitations.templates.velvet-royal',
        'deco-noir.v1' => 'invitations.templates.deco-noir',
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
