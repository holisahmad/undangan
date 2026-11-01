<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Lead;
use App\Notifications\NewEnquiryReceived;
use App\Support\Mail\SandboxMailbox;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

/**
 * Resend's onboarding sender delivers only to the account owner, so one inbox
 * receives everything until a domain is verified (config/mail.php, redirect_to).
 * These tests prove the redirect changes the envelope but not the content.
 */
final class SandboxMailboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_message_is_funnelled_to_the_studio_inbox(): void
    {
        config(['mail.default' => 'array']);

        app(SandboxMailbox::class)->apply('studio@example.test');

        $this->notifyVisitor();

        $sent = app('mailer')->getSymfonyTransport()->messages()->sole();

        $this->assertSame(['studio@example.test'], $this->recipientsOf($sent));
        // One inbox is only readable if the body still names the visitor.
        $this->assertStringContainsString('Rina', (string) $this->emailOf($sent)->getHtmlBody());
    }

    public function test_a_blank_inbox_leaves_delivery_untouched(): void
    {
        config(['mail.default' => 'array']);

        app(SandboxMailbox::class)->apply('');

        $this->notifyVisitor();

        $sent = app('mailer')->getSymfonyTransport()->messages()->sole();

        $this->assertSame(['rina@example.test'], $this->recipientsOf($sent));
    }

    private function notifyVisitor(): void
    {
        $lead = Lead::create([
            'name' => 'Rina',
            'email' => 'rina@example.test',
            'whatsapp' => '081234567890',
            'message' => 'Halo, saya ingin bertanya soal paket.',
            'source' => 'landing',
            'status' => Lead::STATUS_NEW,
        ]);

        Notification::route('mail', 'rina@example.test')->notify(new NewEnquiryReceived($lead));
    }

    /**
     * SentMessage only promises a raw message; the addresses and the body
     * live on the Mime Email behind it.
     */
    private function emailOf(SentMessage $sent): Email
    {
        $message = $sent->getOriginalMessage();
        $this->assertInstanceOf(Email::class, $message);

        return $message;
    }

    /**
     * @return list<string>
     */
    private function recipientsOf(SentMessage $sent): array
    {
        return array_map(
            static fn ($address): string => (string) $address->getAddress(),
            $this->emailOf($sent)->getTo(),
        );
    }
}
