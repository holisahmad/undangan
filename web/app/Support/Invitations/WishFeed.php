<?php

declare(strict_types=1);

namespace App\Support\Invitations;

use App\Data\PublicInvitation;
use App\Models\Rsvp;
use App\Models\Wedding;

/**
 * Builds the public wish wall shown under the RSVP form.
 *
 * Only two things are published: the guest's display name and the message they
 * explicitly consented to share. Attendance status, party size and contact
 * details never reach this list (docs/PRIVACY_AND_RETENTION.md).
 *
 * Demo and showcase pages never read a real wedding's messages; they use the
 * fictional list below so a portfolio page cannot leak a guest's words.
 */
final class WishFeed
{
    public const PER_PAGE = 6;

    /**
     * @return array{items: list<array{name: string, message: string, at: string, initial: string}>, total: int, page: int, pages: int}
     */
    public static function for(PublicInvitation $invitation, int $page = 1, bool $sample = false): array
    {
        $page = max(1, $page);

        if ($sample) {
            return self::paged(self::sampleItems(), $page);
        }

        $wedding = Wedding::query()->where('slug', $invitation->slug)->first();

        if ($wedding === null) {
            return self::paged([], $page);
        }

        $query = Rsvp::query()
            ->where('show_message', true)
            ->whereNotNull('message')
            ->where('message', '!=', '')
            ->whereHas('guest', static fn ($guest) => $guest->where('wedding_id', $wedding->getKey()));

        $total = (clone $query)->count();

        $items = $query
            ->with(['guest' => static fn ($guest) => $guest->select('id', 'display_name')])
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at')
            ->forPage($page, self::PER_PAGE)
            ->get()
            ->map(static function (Rsvp $rsvp): array {
                $name = trim((string) ($rsvp->guest?->display_name ?? ''));

                if ($name === '') {
                    $name = 'Tamu';
                }

                return [
                    'name' => $name,
                    'message' => (string) $rsvp->message,
                    'at' => $rsvp->submitted_at?->locale('id')->translatedFormat('d M Y') ?? '',
                    'initial' => mb_strtoupper(mb_substr($name, 0, 1)),
                ];
            })
            ->all();

        return self::paged($items, $page, $total);
    }

    /**
     * @param  list<array{name: string, message: string, at: string, initial: string}>  $items
     * @return array{items: list<array{name: string, message: string, at: string, initial: string}>, total: int, page: int, pages: int}
     */
    private static function paged(array $items, int $page, ?int $total = null): array
    {
        $total ??= count($items);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $pages);

        // A real feed arrives already sliced by the query; only the in-memory
        // sample list still needs a window applied.
        if (count($items) === $total) {
            $items = array_slice($items, ($page - 1) * self::PER_PAGE, self::PER_PAGE);
        }

        return [
            'items' => array_values($items),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
        ];
    }

    /**
     * @return list<array{name: string, message: string, at: string, initial: string}>
     */
    private static function sampleItems(): array
    {
        $at = static fn (int $days): string => now()->subDays($days)->locale('id')->translatedFormat('d M Y');

        return [
            ['name' => 'Rani & Dito', 'message' => 'Selamat menempuh hidup baru. Semoga menjadi keluarga yang sakinah, mawaddah, warahmah.', 'at' => $at(1), 'initial' => 'R'],
            ['name' => 'Keluarga Hartono', 'message' => 'Barakallahu lakuma wa baraka alaikuma wa jama\'a bainakuma fii khair. Sampai jumpa di hari bahagia kalian.', 'at' => $at(2), 'initial' => 'K'],
            ['name' => 'Sinta', 'message' => 'Akhirnya! Bahagia selalu ya kalian berdua. Jangan lupa siapkan foto-foto buat kami.', 'at' => $at(3), 'initial' => 'S'],
            ['name' => 'Pak Budi', 'message' => 'Selamat ya, semoga acaranya lancar dan penuh berkah.', 'at' => $at(4), 'initial' => 'P'],
        ];
    }
}
