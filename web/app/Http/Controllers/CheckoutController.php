<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\Payments\PaymentGatewayUnavailable;
use App\Exceptions\Payments\UnknownPaymentProvider;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\PaymentGatewayRegistry;
use App\Support\Audit\AuditLogger;
use App\Support\Payments\BankTransfer;
use App\Support\Payments\PaymentWindow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Starts a payment. The manual bank-transfer flow redirects to the instruction
 * page; a provider gateway redirects to its checkout. Nothing is activated here
 * (docs/PAYMENT_FLOW.md, section 4).
 */
final class CheckoutController extends Controller
{
    public function store(
        Request $request,
        Order $order,
        PaymentGatewayRegistry $gateways,
        AuditLogger $audit,
        PaymentWindow $window,
    ): RedirectResponse {
        $this->authorize('pay', $order);

        if ($order->isPaid()) {
            return redirect()
                ->route('orders.show', $order)
                ->with('status', 'Pesanan ini sudah dibayar.');
        }

        if (in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Refunded], true)) {
            return redirect()
                ->route('orders.show', $order)
                ->withErrors(['checkout' => 'Pesanan ini tidak dapat dibayar lagi.']);
        }

        if (BankTransfer::isDefault()) {
            return $this->startBankTransfer($request, $order, $audit, $window);
        }

        try {
            $gateway = $gateways->defaultGateway();

            // One payment attempt per order: retries reuse the same record so
            // provider callbacks always map back to a single order.
            $payment = Payment::query()->firstOrCreate(
                ['idempotency_key' => 'order:'.$order->getKey()],
                [
                    'order_id' => $order->getKey(),
                    'provider' => $gateway->name(),
                    'amount' => $order->total_amount,
                    'currency' => $order->currency,
                    'status' => PaymentStatus::Initiated,
                ],
            );

            $initiation = $gateway->createPayment($order, $payment);
        } catch (PaymentGatewayUnavailable|UnknownPaymentProvider $exception) {
            // A default provider that is not registered or not available is a
            // configuration problem, not the customer's, so it is explained on
            // the order page instead of throwing.
            return redirect()
                ->route('orders.show', $order)
                ->withErrors(['checkout' => $exception->getMessage()]);
        }

        $payment->forceFill([
            'provider' => $gateway->name(),
            'provider_transaction_id' => $initiation->providerTransactionId,
            'status' => PaymentStatus::Pending,
        ])->save();

        $audit->log(
            action: 'payment.checkout_started',
            resourceType: 'order',
            resourceId: $order->getKey(),
            actor: $request->user(),
            metadata: [
                'payment_id' => $payment->getKey(),
                'provider' => $gateway->name(),
                'public_order_number' => $order->public_order_number,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
            ],
        );

        return redirect()->away($initiation->checkoutUrl);
    }

    /**
     * The transfer flow has no provider redirect: it prepares the instruction
     * page and a single payment attempt that admin verification later settles.
     */
    private function startBankTransfer(
        Request $request,
        Order $order,
        AuditLogger $audit,
        PaymentWindow $window,
    ): RedirectResponse {
        if ($order->paymentWindowClosed()) {
            return redirect()
                ->route('orders.show', $order)
                ->withErrors(['checkout' => 'Batas waktu pembayaran sudah lewat. Buat kode pembayaran baru untuk melanjutkan.']);
        }

        if ($order->unique_code === null || $order->payment_expires_at === null) {
            $window->open($order);
        }

        $payment = Payment::query()->firstOrCreate(
            ['idempotency_key' => 'order:'.$order->getKey()],
            [
                'order_id' => $order->getKey(),
                'provider' => BankTransfer::PROVIDER,
                'provider_transaction_id' => 'BT-'.Str::upper(Str::random(10)),
                'amount' => $order->amountDue(),
                'currency' => $order->currency,
                'status' => PaymentStatus::Pending,
            ],
        );

        if (! $payment->isSucceeded()) {
            $payment->forceFill([
                'provider' => BankTransfer::PROVIDER,
                'amount' => $order->amountDue(),
                'status' => PaymentStatus::Pending,
            ])->save();
        }

        $audit->log(
            action: 'payment.checkout_started',
            resourceType: 'order',
            resourceId: $order->getKey(),
            actor: $request->user(),
            metadata: [
                'payment_id' => $payment->getKey(),
                'provider' => BankTransfer::PROVIDER,
                'public_order_number' => $order->public_order_number,
                'amount' => $order->amountDue(),
                'unique_code' => $order->unique_code,
                'currency' => $order->currency,
            ],
        );

        return redirect()->route('checkout.transfer.show', $order);
    }
}
