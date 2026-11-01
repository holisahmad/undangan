<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Orders\CreateOrder;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\ServiceEntitlement;
use App\Models\User;
use App\Models\Wedding;
use Carbon\CarbonImmutable;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Acceptance tests from docs/PAYMENT_FLOW.md, section 6.
 */
final class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlanSeeder::class);

        // This file covers the signed-notification mechanics of a remote
        // provider, so it opts into the sandbox gateway explicitly. The
        // shipped default is the manual bank transfer (BankTransferPaymentTest),
        // and PAYMENT_FAKE_ENABLED is off in .env, so both switches are set here.
        config([
            'platform.payments.default_gateway' => 'fake',
            'platform.payments.fake.enabled' => true,
        ]);
    }

    public function test_the_plan_catalogue_matches_the_documented_hypotheses(): void
    {
        $this->assertDatabaseCount('plans', 3);
        $this->assertSame(
            ['99.000', '199.000', '349.000'],
            Plan::query()->orderBy('price_amount')->get()
                ->map(static fn (Plan $plan): string => number_format($plan->price_amount, 0, ',', '.'))
                ->all(),
        );
    }

    public function test_a_customer_creates_an_order_with_server_side_pricing(): void
    {
        $customer = User::factory()->create();
        $wedding = Wedding::factory()->for($customer, 'owner')->create();
        $plan = Plan::query()->where('code', 'signature')->firstOrFail();

        $this->actingAs($customer)
            ->post(route('weddings.orders.store', $wedding), [
                'plan_id' => $plan->getKey(),
                // Attempt to influence the price from the browser.
                'total_amount' => 1,
                'subtotal_amount' => 1,
            ])
            ->assertRedirect();

        $order = Order::query()->sole();

        $this->assertSame(OrderStatus::PendingPayment, $order->status);
        $this->assertSame($plan->price_amount, $order->total_amount);
        $this->assertSame($plan->price_amount, $order->subtotal_amount);
        $this->assertSame('IDR', $order->currency);
        $this->assertSame($customer->getKey(), $order->user_id);
        $this->assertSame($wedding->getKey(), $order->wedding_id);
        $this->assertStringStartsWith('INV-', $order->public_order_number);

        $item = $order->items()->sole();
        $this->assertSame($plan->getKey(), $item->plan_id);
        $this->assertSame($plan->price_amount, $item->total_amount);
        $this->assertSame('signature', $item->configuration_snapshot['plan_code']);
        $this->assertSame(18, $item->configuration_snapshot['duration_months']);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'order.created',
            'resource_id' => $order->getKey(),
        ]);
    }

    public function test_only_the_owner_can_order_for_a_wedding(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $wedding = Wedding::factory()->for($owner, 'owner')->create();
        $plan = Plan::query()->firstOrFail();

        $this->actingAs($stranger)
            ->post(route('weddings.orders.store', $wedding), ['plan_id' => $plan->getKey()])
            ->assertForbidden();

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_a_plan_that_is_not_on_sale_cannot_be_ordered(): void
    {
        $customer = User::factory()->create();
        $wedding = Wedding::factory()->for($customer, 'owner')->create();
        $plan = Plan::query()->firstOrFail();
        $plan->update(['status' => Plan::STATUS_INACTIVE]);

        $this->actingAs($customer)
            ->post(route('weddings.orders.store', $wedding), ['plan_id' => $plan->getKey()])
            ->assertNotFound();

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_creates_one_payment_attempt_and_redirects_to_the_provider(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);

        $this->actingAs($customer)
            ->post(route('orders.checkout', $order))
            ->assertRedirectContains(route('checkout.fake.show', $order));

        $payment = Payment::query()->sole();
        $this->assertSame('fake', $payment->provider);
        $this->assertSame('order:'.$order->getKey(), $payment->idempotency_key);
        $this->assertSame('pending', $payment->status->value);

        // Retrying checkout must not create a second attempt.
        $this->actingAs($customer)
            ->post(route('orders.checkout', $order))
            ->assertRedirectContains(route('checkout.fake.show', $order));

        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('audit_logs', ['action' => 'payment.checkout_started']);
    }

    public function test_another_customer_cannot_pay_someone_elses_order(): void
    {
        $customer = User::factory()->create();
        $stranger = User::factory()->create();
        $order = $this->placeOrder($customer);

        $this->actingAs($stranger)->post(route('orders.checkout', $order))->assertForbidden();
        $this->actingAs($stranger)->get(route('orders.show', $order))->assertForbidden();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_a_valid_webhook_activates_the_service_once(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);

        $this->postWebhook($this->payload($order))
            ->assertOk()
            ->assertJsonPath('status', 'accepted')
            ->assertJsonPath('processing_status', 'processed');

        $order->refresh();
        $this->assertTrue($order->isPaid());
        $this->assertNotNull($order->paid_at);

        $payment = Payment::query()->sole();
        $this->assertSame('succeeded', $payment->status->value);
        $this->assertNotNull($payment->provider_transaction_id);
        $this->assertNotNull($payment->paid_at);

        $entitlement = ServiceEntitlement::query()->sole();
        $item = $order->items()->sole();
        $this->assertSame($item->getKey(), $entitlement->order_item_id);
        $this->assertSame($order->wedding_id, $entitlement->wedding_id);
        $this->assertSame('active', $entitlement->status->value);
        $this->assertSame('order', $entitlement->limits_json['source']);
        $this->assertSame(
            18,
            (int) round($entitlement->starts_at->diffInMonths($entitlement->expires_at)),
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'order.paid',
            'resource_id' => $order->getKey(),
        ]);
    }

    public function test_a_duplicate_webhook_does_not_create_a_second_entitlement(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);
        $payload = $this->payload($order);

        $this->postWebhook($payload)->assertOk();
        $this->postWebhook($payload)->assertOk();

        $this->assertDatabaseCount('payment_events', 1);
        $this->assertDatabaseCount('service_entitlements', 1);
    }

    public function test_a_new_event_for_an_already_paid_order_is_ignored(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);

        $this->postWebhook($this->payload($order))->assertOk();
        $this->postWebhook($this->payload($order))->assertOk();

        $this->assertDatabaseCount('payment_events', 2);
        $this->assertDatabaseCount('service_entitlements', 1);
        $this->assertDatabaseHas('audit_logs', ['action' => 'payment.duplicate']);
    }

    public function test_an_invalid_signature_is_rejected(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);

        $this->postWebhook($this->payload($order), signature: 'not-a-real-signature')
            ->assertForbidden()
            ->assertJsonPath('reason', 'invalid_signature');

        $this->assertSame(OrderStatus::PendingPayment, $order->refresh()->status);
        $this->assertDatabaseCount('payment_events', 0);
        $this->assertDatabaseCount('service_entitlements', 0);
    }

    public function test_an_unknown_provider_is_rejected(): void
    {
        $this->postWebhook(['id' => 'evt_x'], provider: 'tidak-ada')->assertNotFound();
    }

    public function test_an_amount_or_currency_mismatch_does_not_activate_the_service(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);

        $this->postWebhook($this->payload($order, ['data' => ['amount' => $order->total_amount - 1]]))
            ->assertOk()
            ->assertJsonPath('processing_status', 'failed');

        $this->assertSame(OrderStatus::PendingPayment, $order->refresh()->status);
        $this->assertDatabaseCount('service_entitlements', 0);
        $this->assertDatabaseHas('audit_logs', ['action' => 'payment.rejected']);

        $this->postWebhook($this->payload($order, ['data' => ['currency' => 'USD']]))
            ->assertOk()
            ->assertJsonPath('processing_status', 'failed');

        $this->assertSame(OrderStatus::PendingPayment, $order->refresh()->status);
        $this->assertDatabaseCount('service_entitlements', 0);
    }

    public function test_a_pending_notification_keeps_the_order_pending(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);

        $this->postWebhook($this->payload($order, [
            'type' => 'payment.pending',
            'data' => ['status' => 'pending', 'paid_at' => null],
        ]))
            ->assertOk()
            ->assertJsonPath('processing_status', 'ignored');

        $this->assertSame(OrderStatus::PendingPayment, $order->refresh()->status);
        $this->assertDatabaseCount('service_entitlements', 0);
    }

    public function test_a_failed_notification_marks_the_order_as_failed_without_activating_it(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);

        $this->postWebhook($this->payload($order, [
            'type' => 'payment.failed',
            'data' => ['status' => 'failed', 'paid_at' => null],
        ]))
            ->assertOk()
            ->assertJsonPath('processing_status', 'processed');

        $this->assertSame(OrderStatus::PaymentFailed, $order->refresh()->status);
        $this->assertDatabaseCount('service_entitlements', 0);
        $this->assertDatabaseHas('payments', ['status' => 'failed']);
    }

    public function test_visiting_the_order_page_does_not_activate_the_service(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);

        $this->actingAs($customer)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee($order->public_order_number);

        $this->assertSame(OrderStatus::PendingPayment, $order->refresh()->status);
        $this->assertDatabaseCount('service_entitlements', 0);
    }

    public function test_the_order_screens_render_for_the_owner(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);

        $this->actingAs($customer)
            ->get(route('weddings.orders.create', $order->wedding))
            ->assertOk()
            ->assertSee('Essential')
            ->assertSee('Signature')
            ->assertSee('Premium');

        $this->actingAs($customer)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee($order->public_order_number);

        $this->actingAs($customer)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Riwayat pembayaran');
    }

    public function test_the_sandbox_checkout_processes_a_signed_notification(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);

        $this->actingAs($customer)
            ->get(route('checkout.fake.show', $order))
            ->assertOk();

        $this->actingAs($customer)
            ->post(route('checkout.fake.complete', $order), ['status' => 'succeeded'])
            ->assertRedirect(route('orders.show', $order));

        $this->assertTrue($order->refresh()->isPaid());
        $this->assertDatabaseCount('service_entitlements', 1);
    }

    public function test_the_sandbox_checkout_is_closed_when_disabled(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);
        config(['platform.payments.fake.enabled' => false]);

        $this->actingAs($customer)->get(route('checkout.fake.show', $order))->assertNotFound();
        $this->actingAs($customer)
            ->post(route('checkout.fake.complete', $order), ['status' => 'succeeded'])
            ->assertNotFound();
    }

    /**
     * The webhook used to trust only its signature, so the secret committed to
     * the repository as a fallback could activate an order even with the sandbox
     * switched off. Availability is now enforced where the provider is resolved.
     */
    public function test_the_sandbox_webhook_is_closed_when_the_sandbox_is_disabled(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);
        config(['platform.payments.fake.enabled' => false]);

        $this->postWebhook($this->payload($order))
            ->assertNotFound()
            ->assertJson(['status' => 'unknown_provider']);

        $this->assertSame(OrderStatus::PendingPayment, $order->refresh()->status);
        $this->assertDatabaseCount('service_entitlements', 0);
    }

    public function test_the_sandbox_webhook_never_accepts_the_placeholder_secret(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);
        $payload = $this->payload($order);

        // This is the value that used to ship as the configuration fallback.
        $raw = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $known = hash_hmac('sha256', $raw, 'fake-sandbox-secret');

        config(['platform.payments.fake.webhook_secret' => null]);

        $this->postWebhook($payload, signature: $known)
            ->assertForbidden()
            ->assertJson(['status' => 'rejected', 'reason' => 'invalid_signature']);

        $this->assertFalse($order->refresh()->isPaid());
        $this->assertDatabaseCount('service_entitlements', 0);
    }

    public function test_the_simulate_command_activates_an_order(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);

        $this->artisan('payments:simulate', ['order' => $order->public_order_number])
            ->assertSuccessful();

        $this->assertTrue($order->refresh()->isPaid());
        $this->assertDatabaseCount('service_entitlements', 1);
    }

    public function test_a_renewal_extends_the_active_period_instead_of_overwriting_it(): void
    {
        $customer = User::factory()->create();
        $wedding = Wedding::factory()->for($customer, 'owner')->create();

        $firstOrder = $this->placeOrder($customer, $wedding);
        $this->postWebhook($this->payload($firstOrder))->assertOk();

        $first = ServiceEntitlement::query()->sole();

        $secondOrder = $this->placeOrder($customer, $wedding);
        $this->postWebhook($this->payload($secondOrder))->assertOk();

        $this->assertDatabaseCount('service_entitlements', 2);

        $second = ServiceEntitlement::query()
            ->whereKeyNot($first->getKey())
            ->sole();

        $this->assertTrue($second->starts_at->equalTo($first->expires_at));
        $this->assertTrue($second->expires_at->greaterThan($first->expires_at));
    }

    /**
     * @param  array{id?: string, type?: string, data?: array<string, mixed>}  $overrides
     * @return array<string, mixed>
     */
    private function payload(Order $order, array $overrides = []): array
    {
        return [
            'id' => $overrides['id'] ?? 'evt_'.Str::lower(Str::random(12)),
            'type' => $overrides['type'] ?? 'payment.succeeded',
            'data' => array_merge([
                'transaction_id' => 'trx_'.Str::lower(Str::random(12)),
                'order_number' => $order->public_order_number,
                'amount' => $order->total_amount,
                'currency' => $order->currency,
                'status' => 'succeeded',
                'paid_at' => CarbonImmutable::now()->toIso8601String(),
            ], $overrides['data'] ?? []),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function postWebhook(array $payload, ?string $signature = null, string $provider = 'fake'): TestResponse
    {
        $raw = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return $this->call(
            method: 'POST',
            uri: route('payments.webhook', ['provider' => $provider], false),
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_FAKE_SIGNATURE' => $signature
                    ?? hash_hmac('sha256', $raw, (string) config('platform.payments.fake.webhook_secret')),
            ],
            content: $raw,
        );
    }

    private function placeOrder(User $customer, ?Wedding $wedding = null): Order
    {
        $wedding ??= Wedding::factory()->for($customer, 'owner')->create();
        $plan = Plan::query()->where('code', 'signature')->firstOrFail();

        return app(CreateOrder::class)->handle($customer, $wedding, $plan);
    }
}
