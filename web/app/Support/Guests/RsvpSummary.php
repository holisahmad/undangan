<?php

declare(strict_types=1);

namespace App\Support\Guests;

use App\Enums\RsvpStatus;
use App\Models\Rsvp;
use App\Models\Wedding;

/**
 * One grouped query builds every RSVP number on the dashboard card, so the
 * figures stay consistent and the page does not grow with the guest list.
 */
final class RsvpSummary
{
    /**
     * @return array{
     *     invited: int,
     *     responded: int,
     *     pending: int,
     *     attending: int,
     *     declined: int,
     *     maybe: int,
     *     attending_people: int,
     *     response_rate: int
     * }
     */
    public function for(Wedding $wedding): array
    {
        $guestIds = $wedding->guests()->select('guests.id');

        /** @var array<string, array{responses: int, people: int}> $byStatus */
        $byStatus = [];

        $rows = Rsvp::query()
            ->whereIn('guest_id', $guestIds)
            ->selectRaw('status, count(*) as responses, coalesce(sum(party_size), 0) as people')
            ->groupBy('status')
            ->get();

        foreach ($rows as $row) {
            $status = $row->status instanceof RsvpStatus ? $row->status->value : (string) $row->status;

            $byStatus[$status] = [
                'responses' => (int) $row->responses,
                'people' => (int) $row->people,
            ];
        }

        $invited = (int) $wedding->guests()->count();
        $attending = $byStatus[RsvpStatus::Attending->value]['responses'] ?? 0;
        $declined = $byStatus[RsvpStatus::Declined->value]['responses'] ?? 0;
        $maybe = $byStatus[RsvpStatus::Maybe->value]['responses'] ?? 0;
        $responded = $attending + $declined + $maybe;
        $people = $byStatus[RsvpStatus::Attending->value]['people'] ?? 0;

        return [
            'invited' => $invited,
            'responded' => $responded,
            'pending' => max($invited - $responded, 0),
            'attending' => $attending,
            'declined' => $declined,
            'maybe' => $maybe,
            'attending_people' => $people,
            'response_rate' => $invited === 0 ? 0 : (int) round($responded / $invited * 100),
        ];
    }
}
