<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Payments\ReviewPaymentConfirmation;
use App\Enums\OrderStatus;
use App\Enums\PaymentConfirmationStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentConfirmation;
use App\Support\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Order ledger plus the manual transfer review queue. Money figures come from
 * stored order totals; nothing here recomputes a price, and the service is only
 * activated through ReviewPaymentConfirmation.
 */
final class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('q')->toString());
        $status = $request->string('status')->toString();

        $orders = Order::query()
            ->with(['user', 'wedding', 'items.plan'])
            ->withCount(['confirmations as pending_confirmations_count' => static fn ($query) => $query->where('status', PaymentConfirmationStatus::Submitted->value)])
            ->when(OrderStatus::tryFrom($status) !== null, static fn ($query) => $query->where('status', $status))
            ->when($search !== '', static function ($query) use ($search): void {
                $query->where(static function ($query) use ($search): void {
                    $query->where('public_order_number', 'like', '%'.$search.'%')
                        ->orWhereHas('user', static fn ($user) => $user->where('email', 'like', '%'.$search.'%'))
                        ->orWhereHas('wedding', static fn ($wedding) => $wedding->where('internal_title', 'like', '%'.$search.'%'));
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $paid = Order::query()->where('status', OrderStatus::Paid);

        return view('admin.orders.index', [
            'orders' => $orders,
            'search' => $search,
            'status' => $status,
            'statuses' => OrderStatus::cases(),
            'stats' => [
                'paid_total' => (clone $paid)->sum('total_amount'),
                'paid_count' => (clone $paid)->count(),
                'paid_this_month' => (clone $paid)
                    ->where('paid_at', '>=', now()->startOfMonth())
                    ->sum('total_amount'),
                'pending_count' => Order::query()
                    ->where('status', OrderStatus::PendingPayment)
                    ->where(static function ($query): void {
                        $query->whereNull('payment_expires_at')
                            ->orWhere('payment_expires_at', '>', now());
                    })
                    ->count(),
                'expired_count' => Order::query()
                    ->where('status', OrderStatus::PendingPayment)
                    ->whereNotNull('payment_expires_at')
                    ->where('payment_expires_at', '<=', now())
                    ->count(),
                'review_count' => PaymentConfirmation::query()
                    ->where('status', PaymentConfirmationStatus::Submitted->value)
                    ->count(),
            ],
        ]);
    }

    public function show(Order $order): View
    {
        $order->load([
            'user',
            'wedding',
            'items.plan',
            'payments',
            'confirmations.submittedBy',
            'confirmations.reviewedBy',
        ]);

        return view('admin.orders.show', [
            'order' => $order,
            'confirmation' => $order->confirmations
                ->first(static fn (PaymentConfirmation $row): bool => $row->status === PaymentConfirmationStatus::Submitted),
        ]);
    }

    public function verify(
        Request $request,
        Order $order,
        ReviewPaymentConfirmation $review,
    ): RedirectResponse {
        $confirmation = $this->pendingConfirmation($order);

        try {
            $review->verify($confirmation, $request->user());
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('admin.orders.show', $order)
                ->withErrors(['payment' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status', 'Pembayaran diverifikasi. Masa aktif undangan sudah ditambahkan dan pemilik diberi tahu.');
    }

    public function reject(
        Request $request,
        Order $order,
        ReviewPaymentConfirmation $review,
    ): RedirectResponse {
        $validated = $request->validate([
            'review_note' => ['required', 'string', 'max:500'],
        ]);

        $confirmation = $this->pendingConfirmation($order);

        try {
            $review->reject($confirmation, $request->user(), $validated['review_note']);
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('admin.orders.show', $order)
                ->withErrors(['payment' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status', 'Konfirmasi ditolak. Pemilik dapat mengunggah bukti baru selama batas waktu masih terbuka.');
    }

    public function proof(
        Request $request,
        Order $order,
        PaymentConfirmation $confirmation,
        AuditLogger $audit,
    ): StreamedResponse {
        abort_unless((string) $confirmation->order_id === (string) $order->getKey(), 404);

        $diskName = (string) config('platform.payments.bank_transfer.proof_disk', 'local');
        $disk = Storage::disk($diskName);

        abort_unless($disk->exists($confirmation->proof_path), 404);

        $audit->log(
            action: 'payment.proof_downloaded',
            resourceType: 'order',
            resourceId: $order->getKey(),
            actor: $request->user(),
            metadata: [
                'confirmation_id' => $confirmation->getKey(),
                'public_order_number' => $order->public_order_number,
            ],
        );

        return $disk->download(
            $confirmation->proof_path,
            $confirmation->proof_original_name ?? 'bukti-transfer',
        );
    }

    private function pendingConfirmation(Order $order): PaymentConfirmation
    {
        $confirmation = $order->confirmations()
            ->where('status', PaymentConfirmationStatus::Submitted->value)
            ->latest()
            ->first();

        abort_if($confirmation === null, 404, 'Tidak ada konfirmasi pembayaran yang menunggu verifikasi.');

        return $confirmation;
    }
}
