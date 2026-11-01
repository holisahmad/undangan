<?php

declare(strict_types=1);

namespace App\Support\Templates;

use App\Models\InvitationTemplate;

/**
 * Presentation vocabulary for the public template catalogue. Templates are code
 * (docs/DATABASE_SCHEMA.md, section 4), so the colours that draw a card belong
 * here instead of a database column: a new renderer ships with its own entry and
 * a design never depends on an uploaded image to be recognisable.
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
     * Card palettes keyed by template slug. `frame`, `accent` and `font` draw the
     * miniature invitation; `swatch` is the gradient band above it. The swatch is
     * a literal Tailwind class so the scanner can compile the hex stops from here.
     *
     * @var array<string, array{frame: string, accent: string, font: string, swatch: string}>
     */
    private const CARD_THEMES = [
        'classic-ivory' => [
            'frame' => 'border-[#e4dbc9] bg-[#fbf8f1] text-[#3a352d]',
            'accent' => 'text-[#a98b5f]',
            'font' => 'font-serif',
            'swatch' => 'bg-linear-to-br from-[#fbf8f1] via-[#e4dbc9] to-[#a98b5f]',
        ],
        'modern-editorial' => [
            'frame' => 'border-stone-900 bg-white text-stone-900',
            'accent' => 'text-stone-500',
            'font' => 'font-sans',
            'swatch' => 'bg-linear-to-br from-white via-stone-200 to-stone-900',
        ],
        'garden-botanical' => [
            'frame' => 'border-[#b7c9a8] bg-[#eef2e6] text-[#2f3b2f]',
            'accent' => 'text-[#5b7553]',
            'font' => 'font-serif',
            'swatch' => 'bg-linear-to-br from-[#eef2e6] via-[#b7c9a8] to-[#5b7553]',
        ],
        'emerald-gilded' => [
            'frame' => 'border-[#c8a96a] bg-[#04231b] text-[#f3ecdc]',
            'accent' => 'text-[#c8a96a]',
            'font' => 'font-serif',
            'swatch' => 'bg-linear-to-br from-[#04231b] via-[#0b4334] to-[#c8a96a]',
        ],
        'sage-keepsake' => [
            'frame' => 'border-[#7e9474] bg-[#a9b79e] text-[#2f2b25]',
            'accent' => 'text-[#34524a]',
            'font' => 'font-serif',
            'swatch' => 'bg-linear-to-br from-[#dfe6d6] via-[#a9b79e] to-[#34524a]',
        ],
        'batik-pusaka' => [
            'frame' => 'border-[#c9a227] bg-[#3d1216] text-[#f7efdf]',
            'accent' => 'text-[#e6c98a]',
            'font' => 'font-serif',
            'swatch' => 'bg-linear-to-br from-[#3d1216] via-[#6b1f22] to-[#c9a227]',
        ],
        'midnight-celestial' => [
            'frame' => 'border-[#e3c9a0] bg-[#080d1c] text-[#f6f1e6]',
            'accent' => 'text-[#e3c9a0]',
            'font' => 'font-serif',
            'swatch' => 'bg-linear-to-br from-[#080d1c] via-[#1b2652] to-[#e3c9a0]',
        ],
        'terracotta-sunset' => [
            'frame' => 'border-[#b4552f] bg-[#f7ecdc] text-[#33201a]',
            'accent' => 'text-[#b4552f]',
            'font' => 'font-serif',
            'swatch' => 'bg-linear-to-br from-[#f7ecdc] via-[#e79a5c] to-[#b4552f]',
        ],
        'glasshouse-minimal' => [
            'frame' => 'border-[#c7ccc4] bg-[#f7f6f1] text-[#1d211e]',
            'accent' => 'text-[#63776a]',
            'font' => 'font-sans',
            'swatch' => 'bg-linear-to-br from-[#f7f6f1] via-[#dfe4dc] to-[#63776a]',
        ],
        'pesisir-senja' => [
            'frame' => 'border-[#8fb4b8] bg-[#eef6f5] text-[#123033]',
            'accent' => 'text-[#2c6f74]',
            'font' => 'font-serif',
            'swatch' => 'bg-linear-to-br from-[#eef6f5] via-[#9cc6c9] to-[#e0a06a]',
        ],
        'songket-empress' => [
            'frame' => 'border-[#d8b457] bg-[#fdf6e4] text-[#4a3312]',
            'accent' => 'text-[#a4761f]',
            'font' => 'font-serif',
            'swatch' => 'bg-linear-to-br from-[#fdf6e4] via-[#ecd28a] to-[#a4761f]',
        ],
        'velvet-royal' => [
            'frame' => 'border-[#c9a24b] bg-[#2c0f26] text-[#f8ecf4]',
            'accent' => 'text-[#c9a24b]',
            'font' => 'font-serif',
            'swatch' => 'bg-linear-to-br from-[#2c0f26] via-[#5a1f47] to-[#c9a24b]',
        ],
        'deco-noir' => [
            'frame' => 'border-[#cbb27a] bg-[#111114] text-[#f2ead6]',
            'accent' => 'text-[#cbb27a]',
            'font' => 'font-serif',
            'swatch' => 'bg-linear-to-br from-[#111114] via-[#3a3630] to-[#cbb27a]',
        ],
    ];

    /**
     * @var array{frame: string, accent: string, font: string, swatch: string}
     */
    private const FALLBACK_THEME = [
        'frame' => 'border-stone-300 bg-white text-stone-900',
        'accent' => 'text-stone-500',
        'font' => 'font-serif',
        'swatch' => 'bg-linear-to-br from-stone-50 via-stone-200 to-stone-400',
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
     * @return array{frame: string, accent: string, font: string, swatch: string}
     */
    public static function cardTheme(InvitationTemplate $template): array
    {
        return self::CARD_THEMES[$template->slug] ?? self::FALLBACK_THEME;
    }
}
