<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsEvent;
use Illuminate\View\View;

/**
 * Privacy-friendly funnel: aggregate counts only, compared against the previous
 * 30-day window. No per-person rows are ever shown.
 */
final class AnalyticsController extends Controller
{
    private const LABELS = [
        AnalyticsEvent::LANDING_VIEWED => 'Landing dilihat',
        AnalyticsEvent::LEAD_CAPTURED => 'Lead masuk',
        AnalyticsEvent::REGISTER_COMPLETED => 'Pendaftaran selesai',
        AnalyticsEvent::INVITATION_PUBLISHED => 'Undangan terbit',
        AnalyticsEvent::INVITATION_VIEWED => 'Undangan dibuka',
        AnalyticsEvent::RSVP_SUBMITTED => 'RSVP dikirim',
    ];

    private const FUNNEL = [
        AnalyticsEvent::LANDING_VIEWED,
        AnalyticsEvent::LEAD_CAPTURED,
        AnalyticsEvent::REGISTER_COMPLETED,
        AnalyticsEvent::INVITATION_PUBLISHED,
        AnalyticsEvent::INVITATION_VIEWED,
        AnalyticsEvent::RSVP_SUBMITTED,
    ];

    public function index(): View
    {
        $since = now()->subDays(30);
        $previousSince = now()->subDays(60);

        $current = $this->counts($since);
        $previous = $this->counts($previousSince, $since);

        $funnel = [];
        $previousCount = null;

        foreach (self::FUNNEL as $event) {
            $count = (int) ($current[$event] ?? 0);
            $funnel[] = [
                'event' => $event,
                'label' => self::LABELS[$event],
                'count' => $count,
                'rate' => $previousCount === null || $previousCount === 0
                    ? null
                    : (int) round($count / $previousCount * 100),
            ];
            $previousCount = $count;
        }

        return view('admin.analytics.index', [
            'funnel' => $funnel,
            'rows' => collect(self::FUNNEL)->map(static fn (string $event): array => [
                'label' => self::LABELS[$event],
                'current' => (int) ($current[$event] ?? 0),
                'previous' => (int) ($previous[$event] ?? 0),
            ])->all(),
            'totalEvents' => array_sum($current),
            'since' => $since,
            'windowDays' => 30,
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function counts(\DateTimeInterface $from, ?\DateTimeInterface $to = null): array
    {
        return AnalyticsEvent::query()
            ->where('occurred_at', '>=', $from)
            ->when($to !== null, static fn ($query) => $query->where('occurred_at', '<', $to))
            ->selectRaw('name, count(*) as total')
            ->groupBy('name')
            ->pluck('total', 'name')
            ->map(static fn ($total): int => (int) $total)
            ->all();
    }
}
