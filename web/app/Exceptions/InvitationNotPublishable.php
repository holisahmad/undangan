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
     * @return list<string>
     */
    public function reasons(): array
    {
        return $this->reasons;
    }
}
