<?php

declare(strict_types=1);

namespace App\Support\Templates;

use App\Models\InvitationTemplate;

/**
 * Presentation vocabulary for the public template catalogue. Templates are code
 * (docs/DATABASE_SCHEMA.md, section 4), so the colours that draw a card belong
 * here instead of a database column: a new renderer ships with its own entry and
 * a design never depends on an uploaded image to be recognisable.
 *
 * `cover` selects the miniature art direction drawn by
 * `templates/partials/mini-cover.blade.php`; the palette and type stack are
 * written as inline custom properties so the card can be a faithful, quiet
 * reduction of the real renderer instead of one generic layout in a new colour.
 */
final class TemplatePresentation
{
    /**
     * Display labels for the category column, in catalogue order.
     *
     * @var array<string, string>
     */
    public const CATEGORIES = [
        'classic' => 'Klasik',
        'modern' => 'Modern',
        'minimal' => 'Minimalis',
        'botanical' => 'Botani',
        'coastal' => 'Pesisir',
        'heritage' => 'Adat & Budaya',
        'luxury' => 'Mewah',
    ];

    /**
     * Tier explanation as told to a prospective customer.
     *
     * @var array<string, string>
     */
    public const TIER_LABELS = [
        InvitationTemplate::TIER_ESSENTIAL => 'Semua paket',
        InvitationTemplate::TIER_SIGNATURE => 'Paket Signature ke atas',
        InvitationTemplate::TIER_PREMIUM => 'Eksklusif paket Premium',
    ];

    /**
     * @var array<string, string>
     */
    public const BADGE_LABELS = [
        InvitationTemplate::BADGE_NEW => 'Baru',
        InvitationTemplate::BADGE_BEST => 'Favorit',
    ];

    /**
     * Card art direction per template slug.
     *
     * - cover: miniature variant drawn by the mini-cover partial.
     * - paper/ink/accent/line: the renderer's own palette, not an invented one.
     * - display: the type family the real renderer uses for the couple's names.
     * - script: set only when the design genuinely writes the names in script.
     *
     * @var array<string, array{cover: string, paper: string, ink: string, accent: string, line: string, display: string, script: string}>
     */
    private const CARD_ART = [
        'classic-ivory' => [
            'cover' => 'frame',
            'paper' => '#FBF8F1',
            'ink' => '#3A352D',
            'accent' => '#A98B5F',
            'line' => '#E4DBC9',
            'display' => "'Playfair Display', Georgia, serif",
            'script' => '',
        ],
        'modern-editorial' => [
            'cover' => 'editorial',
            'paper' => '#FFFFFF',
            'ink' => '#171717',
            'accent' => '#A4402A',
            'line' => '#E5E5E5',
            'display' => "'Cormorant Garamond', Georgia, serif",
            'script' => '',
        ],
        'garden-botanical' => [
            'cover' => 'botanical',
            'paper' => '#F1F3E8',
            'ink' => '#2F3B2F',
            'accent' => '#5B7553',
            'line' => '#C3CEB4',
            'display' => "'Cormorant Garamond', Georgia, serif",
            'script' => '',
        ],
        'emerald-gilded' => [
            'cover' => 'gilt',
            'paper' => '#04231B',
            'ink' => '#F3ECDC',
            'accent' => '#C8A96A',
            'line' => '#C8A96A',
            'display' => "'Cormorant Garamond', Georgia, serif",
            'script' => "'Great Vibes', cursive",
        ],
        'sage-keepsake' => [
            'cover' => 'keepsake',
            'paper' => '#F8F3E8',
            'ink' => '#2F2B25',
            'accent' => '#7E9474',
            'line' => '#C9B898',
            'display' => "'Cormorant Garamond', Georgia, serif",
            'script' => "'Alex Brush', cursive",
        ],
        'batik-pusaka' => [
            'cover' => 'heritage',
            'paper' => '#3D1216',
            'ink' => '#F7EFDF',
            'accent' => '#E6C98A',
            'line' => '#8E6A2A',
            'display' => "'Playfair Display', Georgia, serif",
            'script' => '',
        ],
        'midnight-celestial' => [
            'cover' => 'celestial',
            'paper' => '#080D1C',
            'ink' => '#F6F1E6',
            'accent' => '#E3C9A0',
            'line' => '#26304F',
            'display' => "'Cormorant Garamond', Georgia, serif",
            'script' => "'Great Vibes', cursive",
        ],
        'terracotta-sunset' => [
            'cover' => 'sunset',
            'paper' => '#F7ECDC',
            'ink' => '#33201A',
            'accent' => '#B4552F',
            'line' => '#E0BFA0',
            'display' => "'Playfair Display', Georgia, serif",
            'script' => '',
        ],
        'glasshouse-minimal' => [
            'cover' => 'window',
            'paper' => '#F7F6F1',
            'ink' => '#1D211E',
            'accent' => '#63776A',
            'line' => '#C7CCC4',
            'display' => "'Cormorant Garamond', Georgia, serif",
            'script' => '',
        ],
        'pesisir-senja' => [
            'cover' => 'coastal',
            'paper' => '#EEF6F5',
            'ink' => '#123033',
            'accent' => '#2C6F74',
            'line' => '#9CC6C9',
            'display' => "'Cormorant Garamond', Georgia, serif",
            'script' => '',
        ],
        'songket-empress' => [
            'cover' => 'songket',
            'paper' => '#FDF6E4',
            'ink' => '#4A3312',
            'accent' => '#A4761F',
            'line' => '#D8B457',
            'display' => "'Playfair Display', Georgia, serif",
            'script' => '',
        ],
        'velvet-royal' => [
            'cover' => 'royal',
            'paper' => '#2C0F26',
            'ink' => '#F8ECF4',
            'accent' => '#C9A24B',
            'line' => '#6B3B58',
            'display' => "'Cormorant Garamond', Georgia, serif",
            'script' => '',
        ],
        'deco-noir' => [
            'cover' => 'deco',
            'paper' => '#111114',
            'ink' => '#F2EAD6',
            'accent' => '#CBB27A',
            'line' => '#3A3630',
            'display' => "'Cinzel', Georgia, serif",
            'script' => '',
        ],
    ];

    /**
     * @var array{cover: string, paper: string, ink: string, accent: string, line: string, display: string, script: string}
     */
    private const FALLBACK_ART = [
        'cover' => 'frame',
        'paper' => '#FFFFFF',
        'ink' => '#1C1917',
        'accent' => '#9F1239',
        'line' => '#E7E5E4',
        'display' => "'Playfair Display', Georgia, serif",
        'script' => '',
    ];

    public static function categoryLabel(?string $category): string
    {
        return self::CATEGORIES[$category ?? ''] ?? 'Umum';
    }

    public static function tierLabel(?string $tier): string
    {
        return self::TIER_LABELS[$tier ?? InvitationTemplate::TIER_ESSENTIAL]
            ?? self::TIER_LABELS[InvitationTemplate::TIER_ESSENTIAL];
    }

    public static function badgeLabel(?string $badge): ?string
    {
        return self::BADGE_LABELS[$badge ?? ''] ?? null;
    }

    /**
     * @return array{cover: string, paper: string, ink: string, accent: string, line: string, display: string, script: string}
     */
    public static function card(InvitationTemplate $template): array
    {
        return self::CARD_ART[$template->slug] ?? self::FALLBACK_ART;
    }
}
