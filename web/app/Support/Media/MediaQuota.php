<?php

declare(strict_types=1);

namespace App\Support\Media;

use App\Models\Wedding;
use Illuminate\Validation\ValidationException;

/**
 * Photo quota comes from the frozen package snapshot of the active
 * entitlement, mirroring GuestQuota. A missing or negative limit means
 * unlimited, which keeps support grants working.
 */
final class MediaQuota
{
    public function photoLimit(Wedding $wedding): ?int
    {
        return $this->limit($wedding, 'gallery_photos');
    }

    public function bytesLimit(Wedding $wedding): ?int
    {
        return $this->limit($wedding, 'gallery_bytes');
    }

    public function photosUsed(Wedding $wedding): int
    {
        return $wedding->mediaAssets()->countable()->count();
    }

    public function bytesUsed(Wedding $wedding): int
    {
        return (int) $wedding->mediaAssets()->countable()->sum('byte_size');
    }

    /**
     * @throws ValidationException
     */
    public function ensureCapacity(Wedding $wedding, int $additionalPhotos, int $additionalBytes): void
    {
        $photoLimit = $this->photoLimit($wedding);

        if ($photoLimit !== null) {
            $used = $this->photosUsed($wedding);

            if ($used + $additionalPhotos > $photoLimit) {
                throw ValidationException::withMessages([
                    'photos' => "Kuota galeri paket Anda {$photoLimit} foto dan saat ini {$used} tersimpan. "
                        .'Hapus foto lain atau pilih paket dengan kuota lebih besar.',
                ]);
            }
        }

        $bytesLimit = $this->bytesLimit($wedding);

        if ($bytesLimit !== null) {
            $usedBytes = $this->bytesUsed($wedding);

            if ($usedBytes + $additionalBytes > $bytesLimit) {
                throw ValidationException::withMessages([
                    'photos' => 'Kuota penyimpanan galeri paket Anda sudah penuh. '
                        .'Hapus foto lain atau pilih paket dengan kapasitas lebih besar.',
                ]);
            }
        }
    }

    private function limit(Wedding $wedding, string $feature): ?int
    {
        $value = $wedding->activeInvitationEntitlement()?->limits_json['features'][$feature]['limit'] ?? null;

        if (! is_numeric($value)) {
            return null;
        }

        $limit = (int) $value;

        return $limit < 0 ? null : $limit;
    }
}
