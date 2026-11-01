<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Wedding;
use App\Notifications\AccountSuspendedNotification;
use App\Notifications\OrderPaidNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Support\RendersMail;
use Tests\TestCase;

final class NotificationsTest extends TestCase
{
    use RefreshDatabase;
    use RendersMail;

    public function test_a_verified_payment_emails_the_owner_once(): void
    {
        Notification::fake();

        $customer = User::factory()->create();
        $order = $this->orderFor($customer, 'INV-MAIL-1');

        $this->postWebhook($this->payload($order, 'evt_mail_paid'))->assertOk();

        Notification::assertSentToTimes($customer, OrderPaidNotification::class, 1);

        // A second, different event for the same paid order must stay silent.
        $this->postWebhook($this->payload($order, 'evt_mail_paid_again'))->assertOk();

        Notification::assertSentToTimes($customer, OrderPaidNotification::class, 1);
    }

    public function test_a_rejected_payment_does_not_email_the_owner(): void
    {
        Notification::fake();

        $customer = User::factory()->create();
        $order = $this->orderFor($customer, 'INV-MAIL-2');

        $this->postWebhook($this->payload($order, 'evt_mail_mismatch', [
            'amount' => $order->total_amount - 1,
        ]))->assertOk();

        Notification::assertNothingSentTo($customer);
    }

    public function test_suspending_an_account_emails_the_owner_with_the_reason(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.suspend', $customer), ['reason' => 'Spam undangan'])
            ->assertRedirect();

        Notification::assertSentTo(
            $customer,
            AccountSuspendedNotification::class,
            function (AccountSuspendedNotification $notification) use ($customer): bool {
                $html = $this->renderMail($notification->toMail($customer));

                return str_contains($html, 'Spam undangan')
                    && str_contains($html, 'tidak ada yang dihapus')
                    && str_contains($html, (string) config('site.contact.email'));
            },
        );
    }

    private function orderFor(User $customer, string $number): Order
    {
        $wedding = Wedding::factory()->for($customer, 'owner')->create();

        $order = Order::factory()->create([
            'user_id' => $customer->getKey(),
            'wedding_id' => $wedding->getKey(),
            'public_order_number' => $number,
            'total_amount' => 99000,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->getKey(),
            'configuration_snapshot' => [
                'duration_months' => 12,
                'plan_code' => 'signature',
            ],
        ]);

        return $order->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(Order $order, string $eventId, array $data = []): array
    {
        return [
            'id' => $eventId,
            'type' => 'payment.succeeded',
            'data' => array_merge([
                'transaction_id' => 'trx_'.Str::lower(Str::random(12)),
                'order_number' => $order->public_order_number,
                'amount' => $order->total_amount,
                'currency' => $order->currency,
                'status' => 'succeeded',
                'paid_at' => now()->toIso8601String(),
            ], $data),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function postWebhook(array $payload): TestResponse
    {
        $raw = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return $this->call(
            method: 'POST',
            uri: route('payments.webhook', ['provider' => 'fake'], false),
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_FAKE_SIGNATURE' => hash_hmac(
                    'sha256',
                    $raw,
                    (string) config('platform.payments.fake.webhook_secret'),
                ),
            ],
            content: $raw,
        );
    }
}
