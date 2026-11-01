@extends('layouts.app')

@section('title', 'Pesanan')

@section('content')
    <div>
        <h1 class="font-serif text-2xl font-semibold">Pesanan</h1>
        <p class="mt-1 text-sm text-stone-600">Riwayat pembelian paket undangan pada akun ini.</p>
    </div>

    @if ($orders->isEmpty())
        <div class="mt-8 rounded-lg border border-dashed border-stone-300 bg-white p-8 text-center">
            <p class="font-medium">Belum ada pesanan.</p>
            <p class="mt-1 text-sm text-stone-600">Buka salah satu undangan untuk memilih paket.</p>
            <a href="{{ route('weddings.index') }}" class="mt-3 inline-block text-sm text-rose-800 hover:underline">
                Lihat undangan
            </a>
        </div>
    @else
        {{-- Phones get one card per order; the table is the wide-screen view. --}}
        <ul class="mt-6 space-y-3 sm:hidden">
            @foreach ($orders as $order)
                <li class="rounded-lg border border-stone-200 bg-white p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-mono text-xs text-stone-500">{{ $order->public_order_number }}</p>
                            <p class="mt-1 font-medium">{{ $order->wedding?->internal_title ?? '—' }}</p>
                            <p class="mt-0.5 text-sm text-stone-600">{{ $order->items->first()?->plan?->name ?? 'Paket' }}</p>
                        </div>
                        <span class="shrink-0 rounded-full bg-stone-200 px-2 py-0.5 text-xs font-medium">
                            {{ $order->displayStatusLabel() }}
                        </span>
                    </div>

                    <div class="mt-3 flex items-end justify-between gap-3 border-t border-stone-100 pt-3">
                        <div>
                            <p class="text-xs text-stone-500">Harga paket</p>
                            <p class="font-semibold">Rp{{ number_format($order->total_amount, 0, ',', '.') }}</p>
                            @if (! $order->isPaid() && $order->unique_code !== null)
                                <p class="mt-0.5 text-xs text-stone-500">
                                    Transfer {{ $order->amountDueLabel() }} termasuk kode {{ $order->uniqueCodeLabel() }}
                                </p>
                            @endif
                        </div>
                        <a href="{{ route('orders.show', $order) }}"
                           class="shrink-0 rounded-md border border-stone-300 px-3 py-2 text-sm font-medium hover:border-stone-400">
                            Detail
                        </a>
                    </div>

                    <p class="mt-2 text-xs text-stone-500">
                        Dibuat {{ $order->created_at?->locale(app()->getLocale())->translatedFormat('d M Y') }}
                    </p>
                </li>
            @endforeach
        </ul>

        <div class="mt-8 hidden overflow-x-auto rounded-lg border border-stone-200 bg-white sm:block">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs uppercase tracking-wide text-stone-500">
                    <tr>
                        <th class="px-4 py-3">Nomor</th>
                        <th class="px-4 py-3">Undangan</th>
                        <th class="px-4 py-3">Paket</th>
                        <th class="px-4 py-3">Total</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Dibuat</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($orders as $order)
                        <tr>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs">{{ $order->public_order_number }}</td>
                            <td class="px-4 py-3">{{ $order->wedding?->internal_title ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $order->items->first()?->plan?->name ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-medium">Rp{{ number_format($order->total_amount, 0, ',', '.') }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="rounded-full bg-stone-200 px-2 py-0.5 text-xs font-medium">{{ $order->displayStatusLabel() }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-stone-500">
                                {{ $order->created_at?->locale(app()->getLocale())->translatedFormat('d M Y') }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('orders.show', $order) }}" class="text-rose-800 hover:underline">Detail</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $orders->links() }}
        </div>
    @endif
@endsection
