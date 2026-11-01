<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\InvitationTemplate;
use App\Models\TemplateVersion;
use App\Support\Templates\InvitationContentValidator;
use Illuminate\Database\Seeder;

/**
 * Seeds the template versions that ship with the application. Templates are
 * code, not data: every renderer_key must exist in TemplateRegistry.
 */
final class TemplateSeeder extends Seeder
{
    /**
     * The catalogue, in curated order. `tier` is the package a design belongs to
     * (docs/BUSINESS_MODEL.md): a plan only unlocks its own ceiling and below,
     * so the premium rows are what a customer actually pays extra for.
     *
     * slug => [name, category, tier, badge, sort_order, description, renderer_key]
     */
    private const TEMPLATES = [
        'classic-ivory' => [
            'name' => 'Classic Ivory',
            'category' => 'classic',
            'tier' => InvitationTemplate::TIER_ESSENTIAL,
            'badge' => InvitationTemplate::BADGE_BEST,
            'sort_order' => 1,
            'description' => 'Undangan bergaya klasik dengan tipografi serif.',
            'renderer_key' => 'classic-ivory.v1',
        ],
        'emerald-gilded' => [
            'name' => 'Emerald Gilded',
            'category' => 'luxury',
            'tier' => InvitationTemplate::TIER_PREMIUM,
            'badge' => InvitationTemplate::BADGE_BEST,
            'sort_order' => 2,
            'description' => 'Hijau zamrud pekat dengan garis emas, sampul BUKA, dan kode QR undangan.',
            'renderer_key' => 'emerald-gilded.v1',
        ],
        'garden-botanical' => [
            'name' => 'Garden Botanical',
            'category' => 'botanical',
            'tier' => InvitationTemplate::TIER_ESSENTIAL,
            'badge' => null,
            'sort_order' => 3,
            'description' => 'Nuansa taman dengan warna hijau lembut dan bingkai kertas.',
            'renderer_key' => 'garden-botanical.v1',
        ],
        'midnight-celestial' => [
            'name' => 'Midnight Celestial',
            'category' => 'luxury',
            'tier' => InvitationTemplate::TIER_PREMIUM,
            'badge' => InvitationTemplate::BADGE_BEST,
            'sort_order' => 4,
            'description' => 'Langit malam biru tua dengan bulan sabit, bintang, dan aksen champagne.',
            'renderer_key' => 'midnight-celestial.v1',
        ],
        'modern-editorial' => [
            'name' => 'Modern Editorial',
            'category' => 'modern',
            'tier' => InvitationTemplate::TIER_ESSENTIAL,
            'badge' => null,
            'sort_order' => 5,
            'description' => 'Tata letak editorial minimalis dengan tipografi sans-serif.',
            'renderer_key' => 'modern-editorial.v1',
        ],
        'pesisir-senja' => [
            'name' => 'Pesisir Senja',
            'category' => 'coastal',
            'tier' => InvitationTemplate::TIER_ESSENTIAL,
            'badge' => InvitationTemplate::BADGE_NEW,
            'sort_order' => 6,
            'description' => 'Lengkung busur dan ombak dengan cahaya senja pesisir yang hangat.',
            'renderer_key' => 'pesisir-senja.v1',
        ],
        'sage-keepsake' => [
            'name' => 'Sage Keepsake',
            'category' => 'botanical',
            'tier' => InvitationTemplate::TIER_SIGNATURE,
            'badge' => null,
            'sort_order' => 7,
            'description' => 'Kertas miring dua kolom dengan spanduk sage dan ilustrasi bunga cat air.',
            'renderer_key' => 'sage-keepsake.v1',
        ],
        'songket-empress' => [
            'name' => 'Songket Empress',
            'category' => 'heritage',
            'tier' => InvitationTemplate::TIER_SIGNATURE,
            'badge' => InvitationTemplate::BADGE_NEW,
            'sort_order' => 8,
            'description' => 'Kain songket krem dengan anyaman emas dan motif pucuk rebisu.',
            'renderer_key' => 'songket-empress.v1',
        ],
        'batik-pusaka' => [
            'name' => 'Batik Pusaka',
            'category' => 'heritage',
            'tier' => InvitationTemplate::TIER_SIGNATURE,
            'badge' => null,
            'sort_order' => 9,
            'description' => 'Merah marun dan emas dengan motif parang serta gunungan wayang.',
            'renderer_key' => 'batik-pusaka.v1',
        ],
        'terracotta-sunset' => [
            'name' => 'Terracotta Sunset',
            'category' => 'modern',
            'tier' => InvitationTemplate::TIER_SIGNATURE,
            'badge' => null,
            'sort_order' => 10,
            'description' => 'Lengkung tanah liat dan langit senja jingga yang hangat.',
            'renderer_key' => 'terracotta-sunset.v1',
        ],
        'velvet-royal' => [
            'name' => 'Velvet Royal',
            'category' => 'luxury',
            'tier' => InvitationTemplate::TIER_PREMIUM,
            'badge' => InvitationTemplate::BADGE_NEW,
            'sort_order' => 11,
            'description' => 'Beludru plum dengan bingkai emas ganda dan medalion monogram.',
            'renderer_key' => 'velvet-royal.v1',
        ],
        'deco-noir' => [
            'name' => 'Deco Noir',
            'category' => 'luxury',
            'tier' => InvitationTemplate::TIER_PREMIUM,
            'badge' => InvitationTemplate::BADGE_NEW,
            'sort_order' => 12,
            'description' => 'Art deco hitam dan champagne dengan sunburst geometris dan huruf berjarak.',
            'renderer_key' => 'deco-noir.v1',
        ],
        'glasshouse-minimal' => [
            'name' => 'Glasshouse Minimal',
            'category' => 'minimal',
            'tier' => InvitationTemplate::TIER_ESSENTIAL,
            'badge' => null,
            'sort_order' => 13,
            'description' => 'Ruang putih lapang dengan jendela konservatori bergaris tipis.',
            'renderer_key' => 'glasshouse-minimal.v1',
        ],
    ];

    public function run(): void
    {
        foreach (self::TEMPLATES as $slug => $definition) {
            $template = InvitationTemplate::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $definition['name'],
                    'category' => $definition['category'],
                    'tier' => $definition['tier'],
                    'badge' => $definition['badge'],
                    'sort_order' => $definition['sort_order'],
                    'status' => InvitationTemplate::STATUS_ACTIVE,
                    'description' => $definition['description'],
                ],
            );

            TemplateVersion::updateOrCreate(
                ['template_id' => $template->getKey(), 'version' => 1],
                [
                    'renderer_key' => $definition['renderer_key'],
                    // Every shipped template accepts the current payload, so the
                    // optional gift block works on all of them.
                    'config_schema_version' => InvitationContentValidator::SCHEMA_VERSION,
                    'status' => 'published',
                ],
            );
        }
    }
}
