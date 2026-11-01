<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Analytics\TrackEvent;
use App\Models\AnalyticsEvent;
use App\Models\Lead;
use App\Notifications\NewEnquiryReceived;
use App\Support\Marketing\WeddingServices;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Public enquiry form on the landing page. A hidden honeypot absorbs bots and
 * attribution is taken from the query string, never guessed server-side.
 */
final class LeadController extends Controller
{
    public function store(Request $request, TrackEvent $track): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:190'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'message' => ['nullable', 'string', 'max:500'],
            'source' => ['nullable', 'string', 'max:60'],
            // A landing card can prefill the interest through ?layanan=, but the
            // submitted value is checked against the configured services, so a
            // crafted post never stores a label the site does not offer.
            'service_interest' => ['nullable', 'string', 'max:60', Rule::in(WeddingServices::slugs())],
            'utm_source' => ['nullable', 'string', 'max:120'],
            'utm_medium' => ['nullable', 'string', 'max:120'],
            'utm_campaign' => ['nullable', 'string', 'max:120'],
        ]);

        // Bots fill every field; pretend success so they do not retry.
        if (filled($request->input('website'))) {
            return back()->with('lead_status', 'Terima kasih. Kami akan menghubungi Anda.');
        }

        $lead = Lead::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'whatsapp' => $data['whatsapp'] ?? null,
            'message' => $data['message'] ?? null,
            'source' => $data['source'] ?? 'landing',
            'service_interest' => $data['service_interest'] ?? null,
            'utm_source' => $data['utm_source'] ?? null,
            'utm_medium' => $data['utm_medium'] ?? null,
            'utm_campaign' => $data['utm_campaign'] ?? null,
            'status' => Lead::STATUS_NEW,
        ]);

        $track->handle(AnalyticsEvent::LEAD_CAPTURED, null, ['source' => $lead->source]);

        $this->notifyStudio($lead);

        return back()->with('lead_status', 'Terima kasih, '.$lead->name.'. Kami akan menghubungi Anda pada jam kerja.');
    }

    /**
     * The lead is already stored, so a mail outage must neither lose it nor show
     * the visitor an error page.
     */
    private function notifyStudio(Lead $lead): void
    {
        $email = (string) config('site.contact.email');

        if ($email === '') {
            return;
        }

        try {
            Notification::route('mail', $email)->notify(new NewEnquiryReceived($lead));
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
