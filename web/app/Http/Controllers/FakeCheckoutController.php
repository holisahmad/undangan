<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Payments\ProcessPaymentNotification;
use App\Exceptions\Payments\InvalidPaymentSignature;
use App\Models\Order;
use App\Payments\FakePaymentGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use JsonException;

/**
 * Simulated provider checkout for local development and tests. It exists so the
 * webhook path can be demonstrated end to end without a real provider; it is
 * not registered as an available flow in production.
 */
final class FakeCheckoutController extends Controller
{
    public function show(Order $order, FakePaymentGateway $gateway): View
    {
        $this->authorize('pay', $order);
        $this->ensureEnabled($gateway);

        $order->load(['items.plan', 'wedding']);

        return view('checkout.fake', [
            'order' => $order,
        ]);
    }

    public function complete(
        Request $request,
        Order $order,
        FakePaymentGateway $gateway,
        ProcessPaymentNotification $notifications,
    ): RedirectResponse {
        $this->authorize('pay', $order);
        $this->ensureEnabled($gateway);

        if ($order->isPaid()) {
            return redirect()
                ->route('orders.show', $order)
                ->with('status', 'Pesanan ini sudah dibayar.');
        }

        $validated = $request->validate([
            'status' => ['required', 'in:succeeded,failed,expired'],
        ]);

        // Same path a real provider takes: build the body, sign it, then let
        // the notification processor verify signature and order details.
        $payload = $gateway->payloadFor($order, $validated['status']);

        try {
            $raw = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (JsonException) {
            return redirect()
                ->route('orders.show', $order)
                ->withErrors(['checkout' => 'Payload simulasi tidak dapat dibuat.']);
        }

        try {
            $notifications->handle(
                provider: FakePaymentGateway::PROVIDER,
                rawPayload: $raw,
                signature: $gateway->sign($raw),
                payload: $payload,
            );
        } catch (InvalidPaymentSignature) {
            // Without a configured secret the signature cannot be verified by
            // the very endpoint it is posted to, so the demo screen explains the
            // missing setting instead of failing.
            return redirect()
                ->route('orders.show', $order)
                ->withErrors(['checkout' => 'Isi PAYMENT_FAKE_WEBHOOK_SECRET untuk memakai checkout simulasi.']);
        }

        return redirect()
            ->route('orders.show', $order)
            ->with('status', 'Notifikasi pembayaran simulasi telah diproses.');
    }

    /**
     * Availability is read from the gateway itself, so this screen, the webhook
     * and the registry can never disagree about the sandbox being closed.
     */
    private function ensureEnabled(FakePaymentGateway $gateway): void
    {
        abort_unless($gateway->isEnabled(), 404);
    }
}
