<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PaymentConfirmationStatus;
use App\Models\Guest;
use App\Models\Lead;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentConfirmation;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingEvent;
use App\Notifications\AccountSuspendedNotification;
use App\Notifications\NewEnquiryReceived;
use App\Notifications\OrderPaidNotification;
use App\Notifications\PaymentProofSubmitted;
use App\Notifications\RsvpReminderNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Email;
use Tests\Support\RendersMail;
use Tests\TestCase;

/**
 * Every transactional email is a Blade view now, so the only cheap proof that
 * it still works is to render it. These tests assert on what a recipient sees:
 * the call to action, the studio identity, and the absence of template debris.
 */
final class MailTemplateTest extends TestCase
{
    use RefreshDatabase;
    use RendersMail;

    public function test_the_payment_receipt_is_branded_and_links_to_the_order(): void
    {
        $customer = User::factory()->create(['name' => 'Ayu Lestari']);
        $order = $this->orderFor($customer);

        $mail = (new OrderPaidNotification($order))->toMail($customer);
        $html = $this->renderMail($mail);

        $this->assertSame('Pembayaran diterima — '.$order->public_order_number, $mail->subject);
        $this->assertStringContainsString('Halo Ayu Lestari', $html);
        $this->assertStringContainsString($order->public_order_number, $html);
        $this->assertStringContainsString($order->totalLabel(), $html);
        $this->assertStringContainsString('href="'.route('orders.show', $order).'"', $html);
        $this->assertBranded($html);
        $this->assertNoTemplateDebris($mail);
    }

    public function test_the_enquiry_email_quotes_the_message_and_offers_a_whatsapp_reply(): void
    {
        $lead = Lead::create([
            'name' => 'Rina',
            'email' => 'rina@example.test',
            'whatsapp' => '0812-3456-7890',
            'message' => "Halo, saya ingin bertanya soal paket.\nAcara bulan Mei.",
            'source' => 'landing',
            'status' => Lead::STATUS_NEW,
        ]);

        $mail = (new NewEnquiryReceived($lead))->toMail((object) []);
        $html = $this->renderMail($mail);

        // The visitor's line break has to survive, otherwise the quote reads as a run-on.
        $this->assertStringContainsString('Halo, saya ingin bertanya soal paket.<br', $html);
        $this->assertStringContainsString('href="mailto:rina@example.test', $html);
        $this->assertStringContainsString('href="https://wa.me/6281234567890"', $html);
        $this->assertStringContainsString('0812-3456-7890', $html);
        $this->assertStringContainsString('formulir halaman utama', $html);
        $this->assertBranded($html);
        $this->assertNoTemplateDebris($mail);
    }

    public function test_the_suspension_email_explains_that_nothing_is_deleted(): void
    {
        $customer = User::factory()->create(['name' => 'Ayu Lestari']);

        $mail = (new AccountSuspendedNotification('Spam undangan'))->toMail($customer);
        $html = $this->renderMail($mail);

        $this->assertStringContainsString('Spam undangan', $html);
        $this->assertStringContainsString('tidak ada yang dihapus', $html);
        $this->assertStringContainsString('https://wa.me/', $html);
        $this->assertBranded($html);
        $this->assertNoTemplateDebris($mail);
    }

    public function test_the_rsvp_reminder_is_personal_and_names_the_event(): void
    {
        $wedding = Wedding::factory()->for(User::factory()->create(), 'owner')->create([
            'internal_title' => 'Pernikahan Ayu & Bima',
        ]);
        WeddingEvent::factory()->for($wedding)->create([
            'title' => 'Akad Nikah',
            'starts_at' => now()->addMonth()->setTime(9, 0),
            'venue_name' => 'Gedung Serbaguna',
        ]);
        $guest = Guest::factory()->for($wedding)->create(['display_name' => 'Bapak Ahmad']);

        $mail = (new RsvpReminderNotification($wedding->refresh(), $guest, 'https://undangan.test/g/token'))
            ->toMail($guest);
        $html = $this->renderMail($mail);

        $this->assertStringContainsString('Bapak Ahmad', $html);
        $this->assertStringContainsString('Akad Nikah', $html);
        $this->assertStringContainsString('https://undangan.test/g/token', $html);
        $this->assertBranded($html);
        $this->assertNoTemplateDebris($mail);
    }

    public function test_the_transfer_proof_email_tells_the_studio_what_to_verify(): void
    {
        $customer = User::factory()->create(['name' => 'Ayu Lestari']);
        $order = $this->orderFor($customer);
        $confirmation = $this->confirmationFor($order, $customer);

        $mail = (new PaymentProofSubmitted($order->refresh(), $confirmation))->toMail((object) []);
        $html = $this->renderMail($mail);
        $text = $this->renderMailText($mail);

        $this->assertSame('Bukti transfer — '.$order->public_order_number, $mail->subject);
        $this->assertStringContainsString('Budi Pengirim', $html);
        $this->assertStringContainsString($confirmation->amountLabel(), $html);
        $this->assertStringContainsString($order->public_order_number, $html);
        $this->assertStringContainsString('bukti-transfer.png', $html);
        $this->assertStringContainsString('Sudah transfer malam ini.', $html);
        $this->assertStringContainsString('href="'.route('admin.orders.show', $order).'"', $html);
        $this->assertStringContainsString('mailto:'.$customer->email, $html);

        // The mail must never read like a receipt: nothing is paid yet.
        $this->assertStringContainsString('belum aktif', $html);
        $this->assertStringContainsString('Budi Pengirim', $text);

        $this->assertBranded($html);
        $this->assertNoTemplateDebris($mail);
    }

    public function test_the_verification_email_carries_the_signed_link(): void
    {
        $customer = User::factory()->create(['name' => 'Ayu Lestari']);

        $mail = (new VerifyEmailNotification)->toMail($customer);
        $html = $this->renderMail($mail);

        $this->assertStringContainsString('/email/verify/', $html);
        $this->assertStringContainsString('signature=', $html);
        $this->assertStringContainsString('Ayu Lestari', $html);
        $this->assertBranded($html);
        $this->assertNoTemplateDebris($mail);
    }

    /**
     * Rendering a view by hand is not the same as delivering it: the mailer
     * merges its own data (a Message under the "message" key) right before the
     * body renders, which once blanked the enquiry quote. This sends every
     * notification through the array transport, the real code path.
     */
    public function test_every_notification_survives_the_real_mailer(): void
    {
        config(['mail.default' => 'array']);

        $customer = User::factory()->create(['name' => 'Ayu Lestari']);
        $order = $this->orderFor($customer);

        $lead = Lead::create([
            'name' => 'Rina',
            'email' => 'rina@example.test',
            'whatsapp' => '081234567890',
            'message' => 'Halo, saya ingin bertanya soal paket.',
            'source' => 'landing',
            'status' => Lead::STATUS_NEW,
        ]);

        $wedding = Wedding::factory()->for(User::factory()->create(), 'owner')->create();
        $guest = Guest::factory()->for($wedding)->create(['display_name' => 'Bapak Ahmad']);

        $expected = [
            $order->public_order_number => new OrderPaidNotification($order),
            'Halo, saya ingin bertanya soal paket.' => new NewEnquiryReceived($lead),
            'Spam undangan' => new AccountSuspendedNotification('Spam undangan'),
            'Konfirmasi kehadiran' => new RsvpReminderNotification($wedding, $guest, 'https://undangan.test/g/token'),
            'Budi Pengirim' => new PaymentProofSubmitted($order, $this->confirmationFor($order, $customer)),
        ];

        foreach ($expected as $notification) {
            Notification::route('mail', 'studio@example.test')->notify($notification);
        }

        $customer->notify(new VerifyEmailNotification);

        $bodies = app('mailer')->getSymfonyTransport()->messages()
            ->map(fn (SentMessage $sent): string => $this->htmlBody($sent))
            ->all();

        $this->assertCount(count($expected) + 1, $bodies);

        foreach (array_keys($expected) as $index => $needle) {
            $this->assertStringContainsString($needle, $bodies[$index]);
        }

        $this->assertStringContainsString('/email/verify/', $bodies[count($expected)]);
    }

    /**
     * SentMessage only promises a raw message; the HTML body lives on Email.
     */
    private function htmlBody(SentMessage $sent): string
    {
        $message = $sent->getOriginalMessage();
        $this->assertInstanceOf(Email::class, $message);

        return (string) $message->getHtmlBody();
    }

    /**
     * The head band, the studio footer, and a plain-text twin are the three
     * things a recipient notices when they are missing.
     */
    private function assertBranded(string $html): void
    {
        $this->assertStringContainsString((string) config('app.name'), $html);
        $this->assertStringContainsString((string) config('site.operator'), $html);
        $this->assertStringContainsString((string) config('site.contact.email'), $html);
        $this->assertStringContainsString((string) config('site.address.street'), $html);
        $this->assertStringContainsString('max-width:600px', $html);
    }

    private function assertNoTemplateDebris(MailMessage $mail): void
    {
        $html = $this->renderMail($mail);
        $text = $this->renderMailText($mail);

        $this->assertStringNotContainsString('{{', $html);
        $this->assertStringNotContainsString('@section', $html);

        $this->assertStringNotContainsString('{{', $text);
        $this->assertStringNotContainsString('@include', $text);
    }

    /**
     * A submitted transfer proof is the one dashboard form whose data the studio
     * only learns from an email: nothing changes in the order at that moment.
     */
    private function confirmationFor(Order $order, User $customer): PaymentConfirmation
    {
        return PaymentConfirmation::create([
            'order_id' => $order->getKey(),
            'submitted_by' => $customer->getKey(),
            'sender_name' => 'Budi Pengirim',
            'sender_bank' => 'BCA',
            'transferred_at' => now()->toDateString(),
            'amount' => $order->amountDue(),
            'note' => 'Sudah transfer malam ini.',
            'proof_path' => 'payment-proofs/'.$order->getKey().'/bukti-transfer.png',
            'proof_original_name' => 'bukti-transfer.png',
            'status' => PaymentConfirmationStatus::Submitted,
        ]);
    }

    private function orderFor(User $customer): Order
    {
        $wedding = Wedding::factory()->for($customer, 'owner')->create();

        $order = Order::factory()->create([
            'user_id' => $customer->getKey(),
            'wedding_id' => $wedding->getKey(),
            'public_order_number' => 'INV-MAIL-TEMPLATE',
            'total_amount' => 99000,
            'paid_at' => now(),
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->getKey(),
            'configuration_snapshot' => ['duration_months' => 12, 'plan_code' => 'signature'],
        ]);

        return $order->refresh();
    }
}
