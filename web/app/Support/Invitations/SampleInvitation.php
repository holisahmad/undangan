<?php

declare(strict_types=1);

namespace App\Support\Invitations;

use App\Data\PublicInvitation;
use App\Models\InvitationTemplate;
use App\Models\TemplateVersion;

/**
 * Builds the read model used by the public template demo pages. The data is
 * fictional and lives in code: a demo must never read a real wedding, and a
 * renderer must be demonstrable before any customer publishes anything.
 */
final class SampleInvitation
{
    public static function build(InvitationTemplate $template, TemplateVersion $version): PublicInvitation
    {
        $startsAt = now()->addMonths(3)->setTime(9, 0);

        return PublicInvitation::fromSnapshot('contoh-'.$template->slug, [
            'template' => [
                'slug' => $template->slug,
                'name' => $template->name,
                'version' => $version->version,
                'renderer_key' => $version->renderer_key,
                'config_schema_version' => $version->config_schema_version,
            ],
            'couple' => [
                [
                    'type' => 'bride',
                    'display_name' => 'Ayu Lestari',
                    'nickname' => 'Ayu',
                    'parents' => 'Bapak H. Sutrisno & Ibu Hj. Aminah',
                    'biography' => null,
                    'instagram' => null,
                ],
                [
                    'type' => 'groom',
                    'display_name' => 'Bima Pratama',
                    'nickname' => 'Bima',
                    'parents' => 'Bapak Ir. Hartono & Ibu Sri Wahyuni',
                    'biography' => null,
                    'instagram' => null,
                ],
            ],
            'events' => [
                [
                    'type' => 'ceremony',
                    'title' => 'Akad Nikah',
                    'starts_at' => $startsAt->toIso8601String(),
                    'ends_at' => $startsAt->copy()->addHour()->toIso8601String(),
                    'starts_label' => $startsAt->copy()->locale('id')->translatedFormat('l, d F Y · H:i'),
                    'ends_label' => $startsAt->copy()->addHour()->timezone(config('app.timezone'))->format('H:i'),
                    'timezone' => config('app.timezone'),
                    'venue_name' => 'Gedung Graha Indah',
                    'address' => 'Jl. Melati No. 12, Bandung',
                    'map_url' => 'https://maps.google.com/?q=Gedung+Graha+Indah+Bandung',
                ],
                [
                    'type' => 'reception',
                    'title' => 'Resepsi',
                    'starts_at' => $startsAt->copy()->addHours(4)->toIso8601String(),
                    'ends_at' => $startsAt->copy()->addHours(8)->toIso8601String(),
                    'starts_label' => $startsAt->copy()->addHours(4)->locale('id')->translatedFormat('l, d F Y · H:i'),
                    'ends_label' => $startsAt->copy()->addHours(8)->timezone(config('app.timezone'))->format('H:i'),
                    'timezone' => config('app.timezone'),
                    'venue_name' => 'Gedung Graha Indah',
                    'address' => 'Jl. Melati No. 12, Bandung',
                    'map_url' => 'https://maps.google.com/?q=Gedung+Graha+Indah+Bandung',
                ],
            ],
            'content' => [
                'cover' => [
                    'title' => 'The Wedding Of',
                    'greeting' => 'Dengan memohon rahmat dan ridho Allah SWT, kami bermaksud menyelenggarakan pernikahan putra-putri kami.',
                    'quote' => 'Dan di antara tanda-tanda kebesaran-Nya ialah Dia menciptakan pasangan-pasangan untukmu dari jenismu sendiri.',
                    'quote_source' => 'QS. Ar-Rum: 21',
                ],
                'story' => [
                    'heading' => 'Cerita Kami',
                    'body' => "Kami bertemu di bangku kuliah pada 2018 dan tumbuh bersama sejak hari itu.\nSetelah lima tahun, kami memutuskan untuk melangkah ke jenjang yang lebih serius.",
                ],
                'features' => [
                    'show_story' => true,
                    'show_events' => true,
                    'show_map' => true,
                    'show_gift' => true,
                ],
                // Demo-only numbers. They are sequential on purpose so nobody
                // mistakes them for a real account.
                'gift' => [
                    'enabled' => true,
                    'note' => 'Tanpa mengurangi rasa hormat, bagi Bapak/Ibu/Saudara/i yang ingin memberikan tanda kasih, dapat melalui rekening berikut.',
                    'address' => 'Jl. Melati No. 12, Bandung (kirim kado)',
                    'accounts' => [
                        ['bank' => 'BCA', 'number' => '1234567890', 'holder' => 'Ayu Lestari'],
                        ['bank' => 'Mandiri', 'number' => '0987654321', 'holder' => 'Bima Pratama'],
                    ],
                ],
            ],
            'published_at' => now()->toIso8601String(),
            'expires_at' => null,
        ]);
    }
}
