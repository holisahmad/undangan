<?php

declare(strict_types=1);

namespace App\Support\Guests;

use App\Models\Wedding;
use Illuminate\Validation\ValidationException;

/**
 * Guest quota comes from the frozen package snapshot of the active
 * entitlement. No entitlement (or a non-numeric limit) means "no limit", which
 * keeps support grants from blocking existing operations. A negative limit
 * means unlimited, matching the package catalogue.
 */
final class GuestQuota
{
    public function limitFor(Wedding $wedding): ?int
    {
        $value = $wedding->activeInvitationEntitlement()?->limits_json['features']['guest_limit']['limit'] ?? null;

        if (! is_numeric($value)) {
            return null;
        }

        $limit = (int) $value;

        return $limit < 0 ? null : $limit;
    }

    public function usedFor(Wedding $wedding): int
    {
        return $wedding->guests()->count();
    }

    /**
     * @throws ValidationException
     */
    public function ensureCapacity(Wedding $wedding, int $additional = 1): void
    {
        $limit = $this->limitFor($wedding);

        if ($limit === null) {
            return;
        }

        $used = $this->usedFor($wedding);

        if ($used + $additional <= $limit) {
            return;
        }

        throw ValidationException::withMessages([
            'guests' => "Kuota tamu paket Anda {$limit} tamu dan saat ini {$used} tersimpan. "
                .'Hapus tamu lain atau pilih paket dengan kuota lebih besar.',
        ]);
    }
}
