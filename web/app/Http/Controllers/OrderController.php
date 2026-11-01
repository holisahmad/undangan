<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Orders\CreateOrder;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Wedding;
use App\Payments\FakePaymentGateway;
use App\Payments\PaymentGatewayRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = $request->user()
            ->orders()
            ->with(['wedding', 'items.plan'])
            ->latest()
            ->paginate(12);

        return view('orders.index', [
            'orders' => $orders,
        ]);
    }

    public function create(Wedding $wedding): View
    {
        $this->authorize('update', $wedding);

        return view('orders.create', [
            'wedding' => $wedding,
            'plans' => Plan::query()->active()->with('features')->orderBy('price_amount')->get(),
            'entitlement' => $wedding->activeInvitationEntitlement(),
        ]);
    }

    public function store(Request $request, Wedding $wedding, CreateOrder $createOrder): RedirectResponse
    {
        $this->authorize('update', $wedding);

        $validated = $request->validate([
            'plan_id' => ['required', 'uuid'],
        ]);

        // Only packages that are still on sale can be bought; the price always
        // comes from the catalogue, never from the request.
        $plan = Plan::query()
            ->active()
            ->with('features')
            ->whereKey($validated['plan_id'])
            ->firstOrFail();

        $order = $createOrder->handle($request->user(), $wedding, $plan);

        return redirect()
            ->route('orders.show', $order)
            ->with('status', 'Pesanan dibuat. Lanjutkan pembayaran untuk mengaktifkan undangan.');
    }

    public function show(Order $order, PaymentGatewayRegistry $gateways): View
    {
        $this->authorize('view', $order);

        $order->load(['items.plan', 'payments', 'wedding', 'confirmations']);

        return view('orders.show', [
            'order' => $order,
            'confirmation' => $order->latestConfirmation(),
            // The sandbox button follows the gateway's own availability instead
            // of restating its switches here.
            'sandboxCheckout' => (string) config('platform.payments.default_gateway') === FakePaymentGateway::PROVIDER
                && $gateways->available(FakePaymentGateway::PROVIDER),
        ]);
    }
}
