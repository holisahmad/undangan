<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Entitlements\GrantInvitationEntitlement;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Temporary operator tool until the order/payment phase ships. It is the only
 * way to activate a wedding without a payment, and every grant is audited.
 */
final class GrantTrialEntitlement extends Command
{
    protected $signature = 'entitlement:grant-trial
        {wedding : Wedding UUID or public slug}
        {--months= : Override the configured duration in months}
        {--actor= : Email of the operator, for the audit log}';

    protected $description = 'Grant an invitation entitlement without a payment (development and support use)';

    public function handle(GrantInvitationEntitlement $grant): int
    {
        $wedding = $this->resolveWedding($this->argument('wedding'));

        if ($wedding === null) {
            $this->error('Undangan tidak ditemukan.');

            return self::FAILURE;
        }

        $months = $this->option('months');
        $actor = null;

        if ($this->option('actor') !== null) {
            $actor = User::query()->where('email', $this->option('actor'))->first();
        }

        $entitlement = $grant->handle(
            $wedding,
            $actor,
            $months !== null ? (int) $months : null,
        );

        $this->info(sprintf(
            'Entitlement %s aktif sampai %s untuk undangan %s.',
            $entitlement->getKey(),
            $entitlement->expires_at?->toIso8601String() ?? 'tanpa batas',
            $wedding->slug,
        ));

        return self::SUCCESS;
    }

    private function resolveWedding(string $identifier): ?Wedding
    {
        $query = Wedding::query();

        return Str::isUuid($identifier)
            ? $query->whereKey($identifier)->first() ?? $query->where('slug', $identifier)->first()
            : $query->where('slug', $identifier)->first();
    }
}
