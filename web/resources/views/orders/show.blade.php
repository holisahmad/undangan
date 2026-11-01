@extends('layouts.app')

@section('title', 'Pesanan '.$order->public_order_number)

@section('content')
    @php
        $paid = $order->isPaid();
        $payment = $order->payments->sortByDesc('created_at')->first();
        $canPay = ! $paid
            && ! in_array($order->status, [\App\Enums\OrderStatus::Cancelled, \App\Enums\OrderStatus::Refunded], true)
            && auth()->user()->can('pay', $order);
    @endphp

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="{{ route('weddings.show', $order->wedding) }}" class="text-sm text-stone-500 hover:underline">
                &larr; {{ $order->wedding?->internal_title ?? 'Undangan' }}
            </a>
            <h1 class="mt-2 font-serif text-2xl font-semibold">Pesanan {{ $order->public_order_number }}</h1>
            <p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-stone-600">
                <span class="rounded-full bg-stone-200 px-2 py-0.5 text-xs font-medium">{{ $order->displayStatusLabel() }}</span>
                <span>{{ $order->created_at?->locale(app()->getLocale())->translatedFormat('d F Y · H:i') }}</span>
            </p>
        </div>
        @if ($paid)
            <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                Dibayar {{ $order->paid_at?->locale(app()->getLocale())->translatedFormat('d F Y · H:i') }}.
                Masa aktif undangan sudah diperpanjang.
            </div>
        @endif
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="rounded-lg border border-stone-200 bg-white p-5">
                <h2 class="font-serif text-lg font-semibold">Rincian</h2>
                <div class="mt-3 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-xs uppercase tracking-wide text-stone-500">
                            <tr>
                                <th class="py-2">Item</th>
                                <th class="py-2">Durasi</th>
                                <th class="py-2 text-right">Harga</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            @foreach ($order->items as $item)
                                <tr>
                                    <td class="py-2 pr-3">
                                        {{ $item->configuration_snapshot['plan_name'] ?? $item->plan?->name ?? 'Paket' }}
                                    </td>
                                    <td class="whitespace-nowrap py-2 pr-3">{{ $item->configuration_snapshot['duration_months'] ?? $item->plan?->duration_months }} bulan</td>
                                    <td class="whitespace-nowrap py-2 text-right">Rp{{ number_format($item->total_amount, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2" class="pt-3 pr-3 text-right text-stone-500">Total</td>
                                <td class="whitespace-nowrap pt-3 text-right text-base font-semibold">Rp{{ number_format($order->total_amount, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>

            <section class="rounded-lg border border-stone-200 bg-white p-5">
                <h2 class="font-serif text-lg font-semibold">Riwayat pembayaran</h2>

                @if ($order->payments->isEmpty())
                    <p class="mt-2 text-sm text-stone-600">Belum ada percobaan pembayaran.</p>
                @else
                    <ul class="mt-3 space-y-2 text-sm">
                        @foreach ($order->payments->sortByDesc('created_at') as $attempt)
                            <li class="flex flex-wrap items-center justify-between gap-2 border-b border-stone-100 pb-2 last:border-0">
                                <span>
                                    <span class="font-medium">{{ $attempt->status->label() }}</span>
                                    <span class="text-stone-500">· {{ $attempt->provider }}</span>
                                    @if ($attempt->provider_transaction_id)
                                        <span class="font-mono text-xs text-stone-400">{{ $attempt->provider_transaction_id }}</span>
                                    @endif
                                </span>
                                <span class="text-stone-500">{{ $attempt->amountLabel() }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <p class="mt-3 text-xs text-stone-500">
                    Status di halaman ini berasal dari server. Pembayaran transfer bank dianggap berhasil
                    setelah admin mencocokkan dana masuk.
                </p>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="rounded-lg border border-stone-200 bg-white p-5">
                <h2 class="font-serif text-lg font-semibold">Pembayaran</h2>

                @if ($paid)
                    <p class="mt-2 text-sm text-stone-600">Pesanan ini sudah lunas.</p>
                @elseif ($canPay)
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-stone-500">Total transfer</dt>
                            <dd class="font-semibold">{{ $order->amountDueLabel() }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-stone-500">Kode unik</dt>
                            <dd class="font-mono">{{ $order->uniqueCodeLabel() ?? '—' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-stone-500">Batas waktu</dt>
                            <dd class="text-right">{{ $order->payment_expires_at?->locale(app()->getLocale())->translatedFormat('d M Y · H:i') ?? '—' }}</dd>
                        </div>
                    </dl>

                    @if ($order->paymentWindowClosed())
                        <p class="mt-3 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                            Kode unik sudah kedaluwarsa. Buat kode baru untuk melanjutkan pembayaran.
                        </p>
                        <form method="POST" action="{{ route('orders.payment.regenerate', $order) }}" class="mt-3">
                            @csrf
                            <button type="submit"
                                    class="w-full rounded-md bg-amber-800 px-4 py-3 text-sm font-medium text-white hover:bg-amber-900 sm:py-2">
                                Buat kode pembayaran baru
                            </button>
                        </form>
                    @else
                        <a href="{{ route('checkout.transfer.show', $order) }}"
                           class="mt-4 block w-full rounded-md bg-rose-800 px-4 py-3 text-center text-sm font-medium text-white hover:bg-rose-900 sm:py-2">
                            Lihat instruksi pembayaran
                        </a>
                    @endif

                    @if ($confirmation)
                        <p class="mt-3 text-xs text-stone-500">
                            Konfirmasi terakhir: {{ $confirmation->status->label() }}
                            ({{ $confirmation->created_at?->locale(app()->getLocale())->translatedFormat('d M Y · H:i') }}).
                        </p>
                    @endif

                    @if ($sandboxCheckout)
                        <form method="POST" action="{{ route('orders.checkout', $order) }}" class="mt-4">
                            @csrf
                            <button type="submit"
                                    class="w-full rounded-md border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-medium text-amber-900 hover:bg-amber-100">
                                Buka checkout sandbox
                            </button>
                        </form>
                    @endif

                    @if ($payment)
                        <p class="mt-3 text-xs text-stone-500">
                            Percobaan terakhir: {{ $payment->status->label() }}
                            ({{ $payment->created_at?->locale(app()->getLocale())->translatedFormat('d M Y · H:i') }}).
                        </p>
                    @endif
                @else
                    <p class="mt-2 text-sm text-stone-600">Pesanan ini tidak dapat dibayar lagi.</p>
                @endif
            </section>

            <section class="rounded-lg border border-stone-200 bg-white p-5">
                <h2 class="font-serif text-lg font-semibold">Setelah dibayar</h2>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-stone-600">
                    <li>Masa aktif undangan ditambahkan sesuai paket.</li>
                    <li>Undangan dapat segera diterbitkan dari halaman undangan.</li>
                    <li>Data lama tidak dihapus saat perpanjangan.</li>
                </ul>
            </section>
        </aside>
    </div>
@endsection
