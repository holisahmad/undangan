<?php

declare(strict_types=1);

namespace App\Support\Invitations;

use App\Data\PublicInvitation;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * Turns the published events into a downloadable calendar file and a Google
 * Calendar link.
 *
 * The file is generated on the server so "Add to calendar" keeps working
 * without JavaScript and without a third-party embed; guests never leave the
 * invitation to save the date.
 */
final class CalendarFile
{
    private const PRODID = '-//Wedly//Invitation//ID';

    public static function build(PublicInvitation $invitation): ?string
    {
        $events = self::events($invitation);

        if ($events === []) {
            return null;
        }

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:'.self::PRODID,
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:'.self::escape($invitation->coupleHeadline() ?: 'Undangan'),
        ];

        foreach ($events as $index => $event) {
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:'.sha1($invitation->slug.'|'.$index.'|'.$event['starts_at']).'@wedly';
            $lines[] = 'DTSTAMP:'.CarbonImmutable::now()->utc()->format('Ymd\THis\Z');
            $lines[] = 'DTSTART:'.$event['starts']->utc()->format('Ymd\THis\Z');
            $lines[] = 'DTEND:'.$event['ends']->utc()->format('Ymd\THis\Z');
            $lines[] = 'SUMMARY:'.self::escape($event['summary']);
            $lines[] = 'LOCATION:'.self::escape($event['location']);
            $lines[] = 'DESCRIPTION:'.self::escape($event['description']);
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", array_map(self::fold(...), $lines))."\r\n";
    }

    /**
     * A Google Calendar "template" link for the first upcoming event.
     */
    public static function googleUrl(PublicInvitation $invitation): ?string
    {
        $events = self::events($invitation);

        if ($events === []) {
            return null;
        }

        $event = $events[0];

        return 'https://calendar.google.com/calendar/render?'.http_build_query([
            'action' => 'TEMPLATE',
            'text' => $event['summary'],
            'dates' => $event['starts']->utc()->format('Ymd\THis\Z').'/'.$event['ends']->utc()->format('Ymd\THis\Z'),
            'details' => $event['description'],
            'location' => $event['location'],
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * @return list<array{starts: CarbonImmutable, ends: CarbonImmutable, summary: string, location: string, description: string, starts_at: string}>
     */
    private static function events(PublicInvitation $invitation): array
    {
        $couple = $invitation->coupleHeadline();
        $result = [];

        foreach ($invitation->events as $event) {
            $starts = self::moment($event['starts_at'] ?? null);

            if ($starts === null) {
                continue;
            }

            $ends = self::moment($event['ends_at'] ?? null) ?? $starts->addHours(2);
            $title = trim((string) ($event['title'] ?? 'Acara'));
            $venue = trim((string) ($event['venue_name'] ?? ''));
            $address = trim((string) ($event['address'] ?? ''));

            $result[] = [
                'starts' => $starts,
                'ends' => $ends->lessThanOrEqualTo($starts) ? $starts->addHours(2) : $ends,
                'summary' => $couple === '' ? $title : $title.' — '.$couple,
                'location' => trim($venue.($address === '' ? '' : ', '.$address)),
                'description' => $couple === ''
                    ? 'Undangan pernikahan.'
                    : 'Undangan pernikahan '.$couple.'.',
                'starts_at' => (string) ($event['starts_at'] ?? ''),
            ];
        }

        usort($result, static fn (array $a, array $b): int => $a['starts']->getTimestamp() <=> $b['starts']->getTimestamp());

        return $result;
    }

    private static function moment(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    private static function escape(string $value): string
    {
        return str_replace(
            ['\\', ';', ',', "\r\n", "\n", "\r"],
            ['\\\\', '\;', '\,', '\n', '\n', '\n'],
            $value,
        );
    }

    /**
     * RFC 5545 folds every line longer than 75 octets with a leading space.
     */
    private static function fold(string $line): string
    {
        if (strlen($line) <= 75) {
            return $line;
        }

        $folded = '';
        $offset = 0;

        while ($offset < strlen($line)) {
            $chunk = mb_strcut($line, $offset, 74);
            $folded .= ($offset === 0 ? '' : "\r\n ").$chunk;
            $offset += strlen($chunk);
        }

        return $folded;
    }
}
