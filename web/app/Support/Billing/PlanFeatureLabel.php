<?php

declare(strict_types=1);

namespace App\Support\Billing;

use App\Models\PlanFeature;

/**
 * Single source for how a plan feature reads in the UI. The catalogue stores
 * feature values as JSON (docs/DATABASE_SCHEMA.md), so presentation is mapped
 * here instead of being re-derived per view.
 */
final class PlanFeatureLabel
{
    private const NAMES = [
        'guest_limit' => 'Kuota tamu',
        'gallery_photos' => 'Foto galeri',
        'template_tier' => 'Akses template',
        'custom_domain' => 'Domain kustom',
        'invitation_active_months' => 'Masa aktif',
    ];

    public static function name(string $featureKey): string
    {
        return self::NAMES[$featureKey] ?? str_replace('_', ' ', $featureKey);
    }

    public static function value(PlanFeature $feature): string
    {
        $value = $feature->value_json ?? [];

        return match ($feature->feature_key) {
            'guest_limit' => number_format((int) ($value['limit'] ?? 0), 0, ',', '.').' tamu',
            'gallery_photos' => (int) ($value['limit'] ?? 0) < 0
                ? 'Tanpa batas'
                : (int) ($value['limit'] ?? 0).' foto',
            'template_tier' => self::templateTier((string) ($value['tier'] ?? '')),
            'custom_domain' => ($value['enabled'] ?? false) ? 'Termasuk' : 'Tidak termasuk',
            default => is_scalar($value) ? (string) $value : '',
        };
    }

    /**
     * The tier is read as a ceiling: it names the most exclusive template group
     * the package unlocks, matching InvitationTemplate::TIER_RANKS.
     */
    private static function templateTier(string $tier): string
    {
        return match ($tier) {
            'all', 'premium' => 'Semua template, termasuk yang eksklusif',
            'signature' => 'Template Signature ke atas',
            'essential', 'classic' => 'Template paket Dasar',
            default => 'Akses template',
        };
    }
}
