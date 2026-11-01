<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Raised when a publication request cannot satisfy a server-side requirement.
 * The message is safe to show to the customer; reasons are keyed for tests.
 */
final class InvitationNotPublishable extends RuntimeException
{
    /**
     * @param  list<string>  $reasons
     */
    private function __construct(string $message, private readonly array $reasons)
    {
        parent::__construct($message);
    }

    public static function missingDraft(): self
    {
        return new self('Draf undangan belum dibuat.', ['missing_draft']);
    }

    /**
     * @param  list<string>  $missing
     */
    public static function incompleteContent(array $missing): self
    {
        return new self(
            'Lengkapi data berikut sebelum menerbitkan: '.implode(', ', $missing).'.',
            $missing,
        );
    }

    public static function templateNotAvailable(): self
    {
        return new self('Template yang dipilih tidak tersedia untuk publikasi.', ['template_unavailable']);
    }

    public static function missingEntitlement(): self
    {
        return new self(
            'Undangan belum memiliki masa aktif. Selesaikan pembayaran atau minta admin mengaktifkan layanan.',
            ['missing_entitlement'],
        );
    }

    /**
     * The theme itself is fine; the package behind the invitation is not. Worded
     * as an upgrade path because the customer already paid for something and the
     * design was offered to them while the draft was still free to choose.
     */
    public static function templateNotInPlan(string $tierLabel): self
    {
        return new self(
            'Tema ini membutuhkan paket yang lebih tinggi ('.$tierLabel.'). '.
                'Pesan paket tersebut, atau ganti tema sebelum menerbitkan.',
            ['template_tier'],
        );
    }

    /**
     * @return list<string>
     */
    public function reasons(): array
    {
        return $this->reasons;
    }
}
