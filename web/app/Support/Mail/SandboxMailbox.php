<?php

declare(strict_types=1);

namespace App\Support\Mail;

use Illuminate\Support\Facades\Mail;

/**
 * Resend's onboarding sender (onboarding@resend.dev) delivers only to the owner
 * of the Resend account, so without a verified domain every customer-facing
 * message would be rejected at the provider. Pointing the whole mailbox at that
 * one inbox keeps the content readable and makes the flow testable before DNS
 * records exist (docs/DEPLOYMENT.md, section 2.1).
 */
final class SandboxMailbox
{
    /**
     * A blank, 'none', or disabled inbox leaves delivery untouched, which is the production setting.
     */
    public function apply(?string $inbox): void
    {
        if (
            $inbox === null
            || trim($inbox) === ''
            || in_array(strtolower(trim($inbox)), ['none', 'null', 'false', 'off', '0', '-'], true)
        ) {
            return;
        }

        // alwaysTo also drops Cc/Bcc, so a blind copy can never leak to a real
        // customer while the sandbox address is in force.
        Mail::alwaysTo(trim($inbox));
    }
}
