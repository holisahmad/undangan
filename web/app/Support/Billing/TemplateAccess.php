<?php

declare(strict_types=1);

namespace App\Support\Billing;

use App\Models\InvitationTemplate;
use App\Models\Plan;
use App\Models\Wedding;
use Illuminate\Support\Collection;

/**
 * Decides which template tier a wedding may use.
 *
 * A plan publishes the highest tier it unlocks (plan_features.template_tier)
 * and an order freezes that value into the entitlement, so the ceiling of a
 * paying customer never moves underneath them (docs/BUSINESS_MODEL.md).
 */
final class TemplateAccess
{
    /**
     * Ceilings a plan can declare. "all" is the wording used by the catalogue;
     * a plan naming a tier directly is accepted too so both spellings rank.
     *
     * @var array<string, int>
     */
    private const PLAN_CEILINGS = [
        'essential' => 1,
        'classic' => 1,
        'signature' => 2,
        'premium' => 3,
        'all' => 3,
    ];

    public function rankFor(?string $tier): int
    {
        return self::PLAN_CEILINGS[$tier ?? ''] ?? 1;
    }

    /**
     * @param  array<string, mixed>|null  $features  Frozen plan features, if any.
     */
    public function ceilingFrom(?array $features): int
    {
        if ($features === null || ! array_key_exists('template_tier', $features)) {
            // Nothing was frozen (a support grant, or a period that predates the
            // tier catalogue). Restricting it would lock a paying customer out of
            // a design they already own, so the ceiling is the top tier.
            return max(self::PLAN_CEILINGS);
        }

        $tier = $features['template_tier']['tier'] ?? null;

        return $this->rankFor(is_string($tier) ? $tier : null);
    }

    public function ceilingFor(Wedding $wedding): int
    {
        $entitlement = $wedding->activeInvitationEntitlement();

        if ($entitlement === null) {
            // Nothing bought yet: the choice is still a draft decision and the
            // package is picked when the order is placed.
            return max(self::PLAN_CEILINGS);
        }

        $features = $entitlement->limits_json['features'] ?? null;

        return $this->ceilingFrom(is_array($features) ? $features : null);
    }

    public function allows(int $ceiling, InvitationTemplate $template): bool
    {
        return $template->tierRank() <= $ceiling;
    }

    /**
     * Cheapest active package that unlocks the template, used for the
     * "hanya di paket X" note. Plans are passed in already loaded so a catalogue
     * page never queries per card.
     *
     * @param  Collection<int, Plan>  $plans
     */
    public function unlockPlanFor(InvitationTemplate $template, Collection $plans): ?Plan
    {
        return $plans
            ->filter(fn (Plan $plan): bool => $this->ceilingFrom($this->featuresOf($plan)) >= $template->tierRank())
            ->sortBy(static fn (Plan $plan): int => $plan->price_amount)
            ->first();
    }

    /**
     * Where a design starts to be usable, as one note per template slug. Cards
     * only get a note when a cheaper package exists that does not include the
     * design, because "available in every package" is not a reason to interrupt
     * the customer.
     *
     * @param  Collection<int, InvitationTemplate>  $templates
     * @param  Collection<int, Plan>  $plans
     * @return array<string, string>
     */
    public function unlockNotes(Collection $templates, Collection $plans): array
    {
        $cheapest = $plans->sortBy(static fn (Plan $plan): int => $plan->price_amount)->first();
        $notes = [];

        foreach ($templates as $template) {
            $plan = $this->unlockPlanFor($template, $plans);

            if ($plan === null || ($cheapest !== null && $plan->is($cheapest))) {
                continue;
            }

            $notes[(string) $template->slug] = 'Paket '.$plan->name.' ke atas';
        }

        return $notes;
    }

    /**
     * The access a plan publishes, shaped exactly like the features an order
     * freezes into an entitlement (`feature_key => value_json`), so a package and
     * a paid order are never read through two different contracts.
     *
     * @return array<string, mixed>
     */
    private function featuresOf(Plan $plan): array
    {
        $feature = $plan->relationLoaded('features')
            ? $plan->features->first(static fn ($item): bool => $item->feature_key === 'template_tier')
            : null;

        // A plan that does not declare the feature is read as the entry tier.
        // Over-stating the package a design needs is a copy problem; under-stating
        // it would advertise a template the customer cannot actually use.
        return ['template_tier' => is_array($feature?->value_json) ? $feature->value_json : ['tier' => 'essential']];
    }
}
