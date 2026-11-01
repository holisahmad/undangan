<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Orders\CreateOrder;
use App\Enums\OrderStatus;
use App\Enums\PaymentConfirmationStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentConfirmation;
use App\Models\Plan;
use App\Models\User;
use App\Models\Wedding;
use App\Notifications\PaymentProofSubmitted;
use Database\Seeders\PlanSeeder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Manual bank-transfer flow from docs/PAYMENT_FLOW.md: the three-digit unique
 * code, the 24 hour window, the proof upload and the operator verification.
 */
final class BankTransferPaymentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Storage::fake() hands back the adapter, which is where the assertion
     * helpers live; Storage::disk() is only documented as the contract.
     */
    private FilesystemAdapter $disk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlanSeeder::class);

        $this->disk = Storage::fake('local');
    }

    public function test_a_new_order_receives_a_three_digit_code_and_a_twenty_four_hour_window(): void
    {
        $customer = User::factory()->create();
        $wedding = Wedding::factory()->for($customer, 'owner')->create();
        $plan = Plan::query()->where('code', 'signature')->firstOrFail();

        $this->actingAs($customer)
            ->post(route('weddings.orders.store', $wedding), ['plan_id' => $plan->getKey()])
            ->assertRedirect();

        $order = Order::query()->sole();

        $this->assertNotNull($order->unique_code);
        $this->assertGreaterThanOrEqual(100, $order->unique_code);
        $this->assertLessThanOrEqual(999, $order->unique_code);
        $this->assertSame(3, strlen((string) $order->uniqueCodeLabel()));
        $this->assertSame($order->total_amount + $order->unique_code, $order->amountDue());
        $this->assertTrue($order->hasOpenPaymentWindow());
        $this->assertEqualsWithDelta(
            now()->addHours(24)->timestamp,
            $order->payment_expires_at->timestamp,
            5,
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'order.created',
            'resource_id' => $order->getKey(),
        ]);
    }

    public function test_two_open_orders_never_share_the_same_code(): void
    {
        config([
            'platform.payments.bank_transfer.unique_code_min' => 100,
            'platform.payments.bank_transfer.unique_code_max' => 101,
        ]);

        $customer = User::factory()->create();
        $wedding = Wedding::factory()->for($customer, 'owner')->create();
        $plan = Plan::query()->where('code', 'signature')->firstOrFail();

        $first = $this->placeOrder($customer, $wedding, $plan);
        $second = $this->placeOrder($customer, $wedding, $plan);

        // Only two codes exist in the frozen range, so both must be used.
        $this->assertSame([100, 101], collect([$first->unique_code, $second->unique_code])->sort()->values()->all());
    }

    public function test_checkout_redirects_to_instructions_listing_every_bank_account(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);

        $this->actingAs($customer)
            ->post(route('orders.checkout', $order))
            ->assertRedirect(route('checkout.transfer.show', $order));

        $payment = Payment::query()->sole();
        $this->assertSame('bank_transfer', $payment->provider);
        $this->assertSame($order->amountDue(), $payment->amount);
        $this->assertSame(PaymentStatus::Pending, $payment->status);

        $this->actingAs($customer)
            ->get(route('checkout.transfer.show', $order))
            ->assertOk()
            ->assertSee('Rekening tujuan')
            ->assertSee('1234567890')
            ->assertSee('9876543210')
            ->assertSee('1122334455')
            ->assertSee($order->amountDueLabel())
            ->assertSee($order->uniqueCodeLabel())
            ->assertSee('Konfirmasi transfer');

        $this->assertDatabaseHas('audit_logs', ['action' => 'payment.checkout_started']);
    }

    public function test_the_instruction_page_requires_the_owner(): void
    {
        $customer = User::factory()->create();
        $stranger = User::factory()->create();
        $order = $this->placeOrder($customer);

        $this->actingAs($stranger)->get(route('checkout.transfer.show', $order))->assertForbidden();
        $this->actingAs($stranger)
            ->post(route('orders.payment.confirm', $order), $this->confirmationPayload($order))
            ->assertForbidden();

        $this->assertDatabaseCount('payment_confirmations', 0);
    }

    public function test_a_proof_upload_waits_for_verification_and_does_not_activate_the_invitation(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);

        $this->actingAs($customer)
            ->post(route('orders.payment.confirm', $order), $this->confirmationPayload($order))
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('status');

        $confirmation = PaymentConfirmation::query()->sole();
        $this->assertSame(PaymentConfirmationStatus::Submitted, $confirmation->status);
        $this->assertSame($order->amountDue(), $confirmation->amount);
        $this->assertSame('Budi Pengirim', $confirmation->sender_name);
        $this->disk->assertExists($confirmation->proof_path);

        $this->assertSame(OrderStatus::PendingPayment, $order->refresh()->status);
        $this->assertDatabaseCount('service_entitlements', 0);
        $this->assertDatabaseHas('audit_logs', ['action' => 'payment.confirmation_submitted']);

        // The page now says the proof is being reviewed instead of the form.
        $this->actingAs($customer)
            ->get(route('checkout.transfer.show', $order))
            ->assertOk()
            ->assertSee('Bukti transfer sudah kami terima')
            ->assertDontSee('Kirim konfirmasi pembayaran');
    }

    public function test_a_submitted_proof_emails_the_studio_inbox(): void
    {
        Notification::fake();

        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);

        $this->actingAs($customer)
            ->post(route('orders.payment.confirm', $order), $this->confirmationPayload($order))
            ->assertRedirect(route('orders.show', $order));

        $studio = (string) config('site.contact.email');

        Notification::assertSentOnDemand(
            PaymentProofSubmitted::class,
            static fn (PaymentProofSubmitted $notification, array $channels, object $notifiable): bool => ($notifiable->routes['mail'] ?? null) === $studio,
        );
    }

    public function test_the_nominal_must_include_the_unique_code(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);

        $this->actingAs($customer)
            ->post(route('orders.payment.confirm', $order), $this->confirmationPayload($order, [
                'amount' => $order->total_amount,
            ]))
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('payment_confirmations', 0);
        $this->disk->assertDirectoryEmpty('payment-proofs');
    }

    public function test_a_second_proof_is_refused_while_one_is_waiting(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);

        $payload = $this->confirmationPayload($order);
        $this->actingAs($customer)->post(route('orders.payment.confirm', $order), $payload)->assertRedirect();

        $this->actingAs($customer)
            ->post(route('orders.payment.confirm', $order), $this->confirmationPayload($order))
            ->assertSessionHasErrors('proof');

        $this->assertDatabaseCount('payment_confirmations', 1);
    }

    public function test_a_transfer_after_the_deadline_is_refused(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);

        $this->travel(25)->hours();

        $this->actingAs($customer)
            ->post(route('orders.payment.confirm', $order), $this->confirmationPayload($order))
            ->assertSessionHasErrors('proof');

        $this->assertDatabaseCount('payment_confirmations', 0);
        $this->assertSame(OrderStatus::PendingPayment, $order->refresh()->status);
        $this->assertSame('Kedaluwarsa', $order->displayStatusLabel());
    }

    public function test_an_expired_code_can_be_regenerated_for_a_fresh_window(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);
        $previousExpiry = $order->payment_expires_at;

        $this->actingAs($customer)->post(route('orders.checkout', $order))->assertRedirect();

        $this->travel(25)->hours();

        $this->actingAs($customer)
            ->get(route('checkout.transfer.show', $order))
            ->assertOk()
            ->assertSee('Batas waktu pembayaran sudah lewat');

        $this->actingAs($customer)
            ->post(route('orders.payment.regenerate', $order))
            ->assertRedirect(route('checkout.transfer.show', $order));

        $order->refresh();

        $this->assertTrue($order->hasOpenPaymentWindow());
        $this->assertTrue($order->payment_expires_at->greaterThan($previousExpiry));
        $this->assertSame(OrderStatus::PendingPayment, $order->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payment.code_regenerated',
            'resource_id' => $order->getKey(),
        ]);

        // The stored attempt follows the new nominal so a late transfer of the
        // old amount can never be mistaken for the current one.
        $this->assertSame($order->amountDue(), Payment::query()->sole()->amount);
        $this->assertNotSame($previousExpiry->timestamp, $order->payment_expires_at->timestamp);
    }

    /**
     * A proof priced against the previous code can never be verified after a
     * regeneration, and leaving it open would block both the owner's new upload
     * and the operator's queue (docs/adr/011-manual-bank-transfer-payments.md).
     */
    public function test_regenerating_a_code_supersedes_a_proof_that_is_still_waiting(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);

        $this->actingAs($customer)
            ->post(route('orders.payment.confirm', $order), $this->confirmationPayload($order))
            ->assertRedirect();

        $stale = PaymentConfirmation::query()->sole();
        $this->assertSame(PaymentConfirmationStatus::Submitted, $stale->status);

        $this->travel(25)->hours();

        $this->actingAs($customer)
            ->post(route('orders.payment.regenerate', $order))
            ->assertRedirect(route('checkout.transfer.show', $order))
            ->assertSessionHas('status', 'Kode pembayaran baru dibuat. Bukti transfer lama dibatalkan karena nominalnya tidak lagi berlaku; unggah bukti untuk nominal yang baru.');

        $stale->refresh();
        $this->assertSame(PaymentConfirmationStatus::Rejected, $stale->status);
        $this->assertNotNull($stale->reviewed_at);
        $this->assertStringContainsString('Kode pembayaran baru', (string) $stale->review_note);

        $order->refresh();
        $this->assertTrue($order->hasOpenPaymentWindow());
        $this->assertFalse($order->hasPendingConfirmation());

        // The owner can confirm the new nominal immediately.
        $this->actingAs($customer)
            ->post(route('orders.payment.confirm', $order), $this->confirmationPayload($order))
            ->assertRedirect();

        $this->assertDatabaseHas('payment_confirmations', [
            'order_id' => $order->getKey(),
            'status' => PaymentConfirmationStatus::Submitted->value,
            'amount' => $order->amountDue(),
        ]);
    }

    public function test_an_open_window_cannot_be_regenerated_early(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);
        $previousCode = $order->unique_code;

        $this->actingAs($customer)
            ->post(route('orders.payment.regenerate', $order))
            ->assertSessionHasErrors('payment');

        $this->assertSame($previousCode, $order->refresh()->unique_code);
    }

    public function test_an_admin_verification_activates_the_invitation_once(): void
    {
        $customer = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $order = $this->placeOrder($customer);

        $this->actingAs($customer)
            ->post(route('orders.payment.confirm', $order), $this->confirmationPayload($order))
            ->assertRedirect();

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Bukti transfer menunggu verifikasi')
            ->assertSee('Budi Pengirim');

        $this->actingAs($admin)
            ->post(route('admin.orders.verify', $order))
            ->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();
        $this->assertTrue($order->isPaid());
        $this->assertNotNull($order->paid_at);
        $this->assertDatabaseCount('service_entitlements', 1);
        $this->assertDatabaseHas('payment_confirmations', [
            'order_id' => $order->getKey(),
            'status' => PaymentConfirmationStatus::Verified->value,
            'reviewed_by' => $admin->getKey(),
        ]);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->getKey(),
            'status' => PaymentStatus::Succeeded->value,
            'amount' => $order->amountDue(),
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'order.paid',
            'resource_id' => $order->getKey(),
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'payment.confirmation_verified']);

        // Nothing is left to review, so a second click cannot add a week twice.
        $this->actingAs($admin)
            ->post(route('admin.orders.verify', $order))
            ->assertNotFound();

        $this->assertDatabaseCount('service_entitlements', 1);
    }

    public function test_a_rejected_proof_keeps_the_order_pending_and_allows_a_retry(): void
    {
        $customer = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $order = $this->placeOrder($customer);

        $this->actingAs($customer)->post(route('orders.payment.confirm', $order), $this->confirmationPayload($order));

        $this->actingAs($admin)
            ->post(route('admin.orders.reject', $order), ['review_note' => 'Nominal tidak sesuai'])
            ->assertRedirect(route('admin.orders.show', $order));

        $confirmation = PaymentConfirmation::query()->sole();
        $this->assertSame(PaymentConfirmationStatus::Rejected, $confirmation->status);
        $this->assertSame('Nominal tidak sesuai', $confirmation->review_note);
        $this->assertSame(OrderStatus::PendingPayment, $order->refresh()->status);
        $this->assertDatabaseCount('service_entitlements', 0);
        $this->assertDatabaseHas('audit_logs', ['action' => 'payment.confirmation_rejected']);

        // A rejection must not close the window: the customer may upload again.
        $this->actingAs($customer)
            ->post(route('orders.payment.confirm', $order), $this->confirmationPayload($order))
            ->assertRedirect(route('orders.show', $order));

        $this->assertDatabaseCount('payment_confirmations', 2);
    }

    public function test_a_rejection_needs_a_reason(): void
    {
        $customer = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $order = $this->placeOrder($customer);

        $this->actingAs($customer)->post(route('orders.payment.confirm', $order), $this->confirmationPayload($order));

        $this->actingAs($admin)
            ->post(route('admin.orders.reject', $order), ['review_note' => ''])
            ->assertSessionHasErrors('review_note');

        $this->assertSame(PaymentConfirmationStatus::Submitted, PaymentConfirmation::query()->sole()->status);
    }

    public function test_verification_refuses_a_confirmation_whose_nominal_was_tampered_with(): void
    {
        $customer = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $order = $this->placeOrder($customer);

        $this->actingAs($customer)->post(route('orders.payment.confirm', $order), $this->confirmationPayload($order));

        // Simulate a row edited outside the application.
        PaymentConfirmation::query()->sole()->forceFill(['amount' => 1000])->save();

        $this->actingAs($admin)
            ->post(route('admin.orders.verify', $order))
            ->assertSessionHasErrors('payment');

        $this->assertSame(OrderStatus::PendingPayment, $order->refresh()->status);
        $this->assertDatabaseCount('service_entitlements', 0);
    }

    public function test_the_proof_file_is_only_downloadable_by_an_admin(): void
    {
        $customer = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $order = $this->placeOrder($customer);

        $this->actingAs($customer)->post(route('orders.payment.confirm', $order), $this->confirmationPayload($order));

        $confirmation = PaymentConfirmation::query()->sole();

        $this->actingAs($customer)
            ->get(route('admin.orders.proof', [$order, $confirmation]))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('admin.orders.proof', [$order, $confirmation]))
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', ['action' => 'payment.proof_downloaded']);
    }

    public function test_the_expire_command_closes_unpaid_attempts_but_keeps_the_order_payable(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);

        $this->actingAs($customer)->post(route('orders.checkout', $order))->assertRedirect();

        $this->travel(25)->hours();

        $this->artisan('payments:expire')->assertSuccessful();

        $this->assertSame(PaymentStatus::Expired, Payment::query()->sole()->status);
        $this->assertSame(OrderStatus::PendingPayment, $order->refresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payment.window_expired',
            'resource_id' => $order->getKey(),
        ]);
    }

    public function test_the_expire_command_leaves_a_submitted_proof_alone(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);

        $this->actingAs($customer)->post(route('orders.payment.confirm', $order), $this->confirmationPayload($order));

        $this->travel(25)->hours();

        $this->artisan('payments:expire')->assertSuccessful();

        $this->assertSame(PaymentStatus::Pending, Payment::query()->sole()->status);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'payment.window_expired']);
    }

    public function test_the_order_detail_page_shows_the_amount_code_and_deadline(): void
    {
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);

        $this->actingAs($customer)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee($order->amountDueLabel())
            ->assertSee($order->uniqueCodeLabel())
            ->assertSee('Lihat instruksi pembayaran');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function confirmationPayload(Order $order, array $overrides = []): array
    {
        return array_merge([
            'sender_name' => 'Budi Pengirim',
            'sender_bank' => 'BCA',
            'transferred_at' => now()->toDateString(),
            'amount' => $order->amountDue(),
            'note' => 'Sudah transfer malam ini.',
            'proof' => UploadedFile::fake()->image('bukti-transfer.png'),
        ], $overrides);
    }

    private function placeOrder(User $customer, ?Wedding $wedding = null, ?Plan $plan = null): Order
    {
        $wedding ??= Wedding::factory()->for($customer, 'owner')->create();
        $plan ??= Plan::query()->where('code', 'signature')->firstOrFail();

        return app(CreateOrder::class)->handle($customer, $wedding, $plan);
    }
}
