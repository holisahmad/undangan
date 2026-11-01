<?php

declare(strict_types=1);

namespace App\Support\Invitations;

use App\Models\Wedding;
use App\Models\WeddingEvent;

/**
 * Human-facing copy reused by the share panel, reminders, and notifications.
 * Only public data is used: couple names, the first event, and the public URL.
 */
final class InvitationCopy
{
    public static function couple(Wedding $wedding): string
    {
        $names = $wedding->partners
            ->pluck('display_name')
            ->filter()
            ->all();

        if ($names !== []) {
            return implode(' & ', $names);
        }

        return $wedding->internal_title;
    }

    public static function eventSummary(Wedding $wedding): ?WeddingEvent
    {
        return $wedding->events
            ->sortBy(static fn (WeddingEvent $event) => $event->starts_at?->getTimestamp() ?? PHP_INT_MAX)
            ->first();
    }

    /**
     * Ready-to-paste broadcast text. The internal working title never leaks.
     */
    public static function shareMessage(Wedding $wedding, string $publicUrl): string
    {
        $lines = ['Undangan pernikahan '.self::couple($wedding)];
        $event = self::eventSummary($wedding);

        if ($event?->starts_at !== null) {
            $lines[] = $event->starts_at->locale(app()->getLocale())->translatedFormat('l, d F Y');
        }

        if ($event?->venue_name) {
            $lines[] = $event->venue_name;
        }

        $lines[] = 'Buka undangan digital: '.$publicUrl;

        return implode("\n", $lines);
    }
}
