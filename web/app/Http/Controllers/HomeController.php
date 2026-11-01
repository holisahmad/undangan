<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Analytics\TrackEvent;
use App\Models\AnalyticsEvent;
use App\Models\InvitationTemplate;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\Testimonial;
use App\Models\Wedding;
use App\Support\Billing\TemplateAccess;
use App\Support\Contact\BusinessAddress;
use App\Support\Contact\PhoneNumber;
use App\Support\Seo\StructuredData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\View\View;

final class HomeController extends Controller
{
    public function index(TrackEvent $track, TemplateAccess $access): View
    {
        $track->handle(AnalyticsEvent::LANDING_VIEWED);

        // The landing page carries a curated strip, not the whole catalogue: the
        // design count keeps growing and /tema owns the filtered, paginated view.
        $templates = $this->publishedTemplates()
            ->limit(max(1, (int) config('platform.marketing.landing_template_limit', 6)))
            ->get();

        $plans = Plan::query()->active()->with('features')->orderBy('price_amount')->get();

        $faqs = config('site.faq', []);

        // Social proof is opt-in twice: the couple submits a quote (it starts
        // as pending) and the couple enables the showcase for the portfolio
        // strip. Nothing private is ever picked here.
        $testimonials = Testimonial::query()
            ->where('status', Testimonial::STATUS_APPROVED)
            ->with(['wedding:id,slug,internal_title', 'user:id,name'])
            ->latest('moderated_at')
            ->limit((int) config('platform.marketing.testimonial_limit', 6))
            ->get();

        $showcaseWeddings = Wedding::query()
            ->where('showcase_enabled', true)
            ->whereHas('activePublication')
            ->with('partners')
            ->latest('showcase_enabled_at')
            ->limit(3)
            ->get();

        return view('welcome', [
            'templates' => $templates,
            'templateCount' => $this->publishedTemplates()->count(),
            'unlockNotes' => $access->unlockNotes($templates, $plans),
            'stats' => $this->stats($plans),
            'plans' => $plans,
            'faqs' => $faqs,
            'testimonials' => $testimonials,
            'showcaseWeddings' => $showcaseWeddings,
            'structuredData' => StructuredData::home(
                siteName: (string) config('app.name'),
                homeUrl: route('home'),
                description: (string) config('site.description'),
                email: (string) config('site.contact.email'),
                logoUrl: asset('icon-512.png'),
                legalName: (string) config('site.operator'),
                telephone: PhoneNumber::e164((string) config('site.contact.phone')),
                address: BusinessAddress::postalAddress((array) config('site.address', [])),
                mapsUrl: (string) config('site.contact.maps_url'),
                faqs: $faqs,
                templates: $templates
                    ->map(static fn (InvitationTemplate $template): array => [
                        'name' => $template->name,
                        'url' => route('templates.demo', ['template' => $template->slug]),
                    ])
                    ->values()
                    ->all(),
            ),
        ]);
    }

    /**
     * A strip card is only useful if it can be opened, so a template counts as
     * landing material once it has a published renderer. A `with` constraint is
     * handed the relation rather than the plain builder.
     *
     * @return Builder<InvitationTemplate>
     */
    private function publishedTemplates(): Builder
    {
        $versions = static fn (HasMany $versions): HasMany => $versions
            ->where('status', 'published')
            ->orderByDesc('version');

        return InvitationTemplate::query()
            ->published()
            ->with(['versions' => $versions])
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * Headline figures for the landing band. Every value is read from the live
     * catalogue, because a counter the page cannot explain is worse than an
     * empty band.
     *
     * @param  Collection<int, Plan>  $plans
     * @return list<array{value: string, label: string}>
     */
    private function stats(Collection $plans): array
    {
        $cheapest = $plans->first();
        $months = (int) ($plans->max(static fn (Plan $plan): int => $plan->duration_months) ?? 0);
        $guests = (int) ($plans
            ->flatMap(static fn (Plan $plan): Collection => $plan->features)
            ->where('feature_key', 'guest_limit')
            ->map(static fn (PlanFeature $feature): int => (int) ($feature->value_json['limit'] ?? 0))
            ->max() ?? 0);

        $stats = [];

        $themes = $this->publishedTemplates()->count();

        if ($themes > 0) {
            $stats[] = ['value' => (string) $themes, 'label' => 'tema yang bisa dibuka sebagai contoh penuh'];
        }

        if ($cheapest !== null) {
            $stats[] = ['value' => 'Mulai '.$cheapest->priceLabel(), 'label' => 'sekali bayar per undangan, tanpa biaya per tamu'];
        }

        if ($guests > 0) {
            $stats[] = ['value' => number_format($guests, 0, ',', '.'), 'label' => 'tamu pada kuota terbesar paket tertinggi'];
        }

        if ($months > 0) {
            $stats[] = ['value' => $months.' bulan', 'label' => 'masa aktif terpanjang, dan dapat diaktifkan kembali'];
        }

        return $stats;
    }
}
