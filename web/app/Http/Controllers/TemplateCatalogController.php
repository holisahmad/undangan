<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\InvitationTemplate;
use App\Models\Plan;
use App\Support\Billing\TemplateAccess;
use App\Support\Seo\StructuredData;
use App\Support\Templates\TemplatePresentation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * The public theme catalogue. Templates keep multiplying, so this page is
 * paginated server-side and the extra pages are fetched as the same rendered
 * fragment: the grid a visitor sees without JavaScript is the grid the
 * load-more control appends.
 */
final class TemplateCatalogController extends Controller
{
    public function __invoke(Request $request, TemplateAccess $access): View|Response
    {
        $category = $this->choice($request->query('kategori'), array_keys(TemplatePresentation::CATEGORIES));
        $tier = $this->choice($request->query('paket'), array_keys(InvitationTemplate::TIER_RANKS));

        $templates = $this->query()
            ->when($category !== null, static fn (Builder $query): Builder => $query->where('category', $category))
            ->when($tier !== null, static fn (Builder $query): Builder => $query->where('tier', $tier))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(max(1, (int) config('platform.marketing.catalog_per_page', 9)))
            ->withQueryString();

        $plans = Plan::query()->active()->with('features')->orderBy('price_amount')->get();

        $filters = array_filter(['kategori' => $category, 'paket' => $tier], static fn (?string $value): bool => $value !== null);

        $data = [
            'templates' => $templates,
            'category' => $category,
            'tier' => $tier,
            'categories' => $this->facets('category', 'tier', $tier),
            'tiers' => $this->facets('tier', 'category', $category),
            // Both the plain link and the fetched fragment are built here so the
            // page and the enhancement can never disagree on the next page.
            'nextUrl' => $templates->hasMorePages() ? $this->pageUrl($templates->currentPage() + 1, $filters, false) : null,
            'fragmentUrl' => $templates->hasMorePages() ? $this->pageUrl($templates->currentPage() + 1, $filters, true) : null,
            'unlockNotes' => $access->unlockNotes(
                $templates->getCollection(),
                $plans,
            ),
        ];

        if ($request->query('bagian') === 'kartu') {
            return response()->view('templates.partials.cards', $data);
        }

        $page = $templates->items();

        return view('templates.catalog', $data + [
            'plans' => $plans,
            'structuredData' => StructuredData::catalog(
                homeUrl: route('home'),
                url: route('templates.index'),
                title: 'Katalog tema undangan pernikahan',
                description: 'Pilih tema undangan digital per kategori dan paket: '.
                    implode(', ', array_map(static fn (InvitationTemplate $template): string => $template->name, $page)).'.',
                total: (int) $templates->total(),
                templates: array_map(
                    static fn (InvitationTemplate $template): array => [
                        'name' => $template->name,
                        'url' => route('templates.demo', ['template' => $template->slug]),
                    ],
                    $page,
                ),
            ),
        ]);
    }

    /**
     * Only templates with a published renderer can be previewed, so an archived
     * row or a half-shipped design never reaches the catalogue. A `with`
     * constraint is handed the relation rather than the plain builder.
     *
     * @return Builder<InvitationTemplate>
     */
    private function query(): Builder
    {
        $versions = static fn (HasMany $versions): HasMany => $versions
            ->where('status', 'published')
            ->orderByDesc('version');

        return InvitationTemplate::query()->published()->with(['versions' => $versions]);
    }

    /**
     * How many themes sit behind each chip. A facet never applies its own
     * selection, because picking a category would otherwise empty out every
     * other category chip and strand the visitor.
     *
     * @return array<string, int>
     */
    private function facets(string $column, string $otherColumn, ?string $otherValue): array
    {
        $rows = InvitationTemplate::query()
            ->published()
            ->when(
                $otherValue !== null,
                static fn (Builder $query): Builder => $query->where($otherColumn, $otherValue),
            )
            ->select($column, DB::raw('count(*) as aggregate'))
            ->groupBy($column)
            ->pluck('aggregate', $column);

        return $rows->map(static fn (mixed $count): int => (int) $count)->all();
    }

    /**
     * @param  array<string, string>  $filters
     */
    private function pageUrl(int $page, array $filters, bool $fragment): string
    {
        return route('templates.index', $filters + [
            'page' => $page,
        ] + ($fragment ? ['bagian' => 'kartu'] : []));
    }

    /**
     * A filter value is only honoured when the catalogue actually knows it; a
     * typed-in value narrows nothing rather than throwing a 404 in the face of
     * a visitor who followed an old link.
     *
     * @param  list<string>  $allowed
     */
    private function choice(mixed $value, array $allowed): ?string
    {
        return is_string($value) && in_array($value, $allowed, true) ? $value : null;
    }
}
