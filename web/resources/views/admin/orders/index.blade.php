@extends('layouts.app')

@section('title', 'Admin &middot; Pesanan')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="font-serif text-2xl font-semibold">Admin &middot; Pesanan</h1>
            <p class="mt-1 text-sm text-stone-600">
                {{ $orders->total() }} pesanan. Angka pendapatan diambil dari total pesanan berstatus dibayar.
            </p>
        </div>
    </div>

    @include('admin.partials.nav')

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border border-stone-200 bg-white p-4">
            <p class="text-xs uppercase tracking-wide text-stone-500">Pendapatan dibayar</p>
            <p class="mt-2 text-xl font-semibold">Rp{{ number_format($stats['paid_total'], 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-stone-500">{{ $stats['paid_count'] }} pesanan lunas</p>
        </div>
        <div class="rounded-lg border border-stone-200 bg-white p-4">
            <p class="text-xs uppercase tracking-wide text-stone-500">Bulan ini</p>
            <p class="mt-2 text-xl font-semibold">Rp{{ number_format($stats['paid_this_month'], 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-stone-500">Dibayar sejak {{ now()->startOfMonth()->translatedFormat('d M Y') }}</p>
        </div>
        <div class="rounded-lg border border-stone-200 bg-white p-4">
            <p class="text-xs uppercase tracking-wide text-stone-500">Menunggu pembayaran</p>
            <p class="mt-2 text-xl font-semibold">{{ $stats['pending_count'] }}</p>
            <p class="mt-1 text-xs text-stone-500">{{ $stats['expired_count'] }} kode kedaluwarsa</p>
        </div>
        <div class="rounded-lg border border-stone-200 bg-white p-4">
            <p class="text-xs uppercase tracking-wide text-stone-500">Rata-rata transaksi</p>
            <p class="mt-2 text-xl font-semibold">
                Rp{{ number_format($stats['paid_count'] > 0 ? intdiv($stats['paid_total'], $stats['paid_count']) : 0, 0, ',', '.') }}
            </p>
            <p class="mt-1 text-xs text-stone-500">Dari pesanan lunas</p>
        </div>
    </div>

    @if ($stats['review_count'] > 0)
        <div class="mt-6 rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900" role="status">
            <p class="font-medium">{{ $stats['review_count'] }} bukti transfer menunggu verifikasi.</p>
            <p class="mt-1 text-sky-800">Buka pesanan bertanda “Perlu verifikasi” untuk mencocokkan dana masuk.</p>
        </div>
    @endif

    <form method="GET" action="{{ route('admin.orders.index') }}" class="mt-6 flex flex-wrap items-end gap-3">
        <div class="w-full flex-1 sm:min-w-[18rem]">
            <label for="q" class="block text-sm font-medium">Cari nomor, email pemilik, atau judul undangan</label>
            <input id="q" name="q" type="search" value="{{ $search }}"
                   class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-rose-700 focus:outline-none focus:ring-1 focus:ring-rose-700">
        </div>
        <div>
            <label for="status" class="block text-sm font-medium">Status</label>
            <select id="status" name="status"
                    class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-rose-700 focus:outline-none focus:ring-1 focus:ring-rose-700">
                <option value="">Semua status</option>
                @foreach ($statuses as $option)
                    <option value="{{ $option->value }}" @selected($status === $option->value)>{{ $option->label() }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="rounded-md bg-stone-900 px-4 py-2 text-sm font-medium text-white hover:bg-stone-800">
            Filter
        </button>
        @if ($search !== '' || $status !== '')
            <a href="{{ route('admin.orders.index') }}" class="text-sm text-stone-600 underline-offset-4 hover:underline">Reset</a>
        @endif
    </form>

    <div class="mt-6 overflow-x-auto rounded-lg border border-stone-200 bg-white">
        <table class="min-w-full divide-y divide-stone-200 text-sm">
            <thead class="bg-stone-50 text-left text-xs uppercase tracking-wide text-stone-500">
                <tr>
                    <th class="px-4 py-3 font-medium">Nomor</th>
                    <th class="px-4 py-3 font-medium">Pemilik</th>
                    <th class="px-4 py-3 font-medium">Undangan</th>
                    <th class="px-4 py-3 font-medium">Paket</th>
                    <th class="px-4 py-3 font-medium">Total</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Dibuat</th>
                    <th class="px-4 py-3 font-medium">Dibayar</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($orders as $order)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs">
                            <a href="{{ route('admin.orders.show', $order) }}"
                               class="text-rose-800 underline-offset-4 hover:underline">{{ $order->public_order_number }}</a>
                            @if ($order->pending_confirmations_count > 0)
                                <span class="mt-1 block w-fit rounded-full bg-sky-100 px-2 py-0.5 font-sans text-[11px] font-medium text-sky-900">
                                    Perlu verifikasi
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($order->user)
                                <a href="{{ route('admin.users.show', $order->user) }}"
                                   class="text-rose-800 underline-offset-4 hover:underline">{{ $order->user->email }}</a>
                            @else
                                <span class="text-stone-500">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($order->wedding)
                                <a href="{{ route('admin.weddings.show', $order->wedding) }}"
                                   class="text-rose-800 underline-offset-4 hover:underline">{{ $order->wedding->internal_title }}</a>
                            @else
                                <span class="text-stone-500">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $order->items->first()?->plan?->name ?? '—' }}</td>
                        <td class="px-4 py-3 font-medium">Rp{{ number_format($order->total_amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">{{ $order->displayStatusLabel() }}</td>
                        <td class="px-4 py-3 text-stone-500">{{ $order->created_at?->translatedFormat('d M Y') }}</td>
                        <td class="px-4 py-3 text-stone-500">{{ $order->paid_at?->translatedFormat('d M Y') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-stone-500">Tidak ada pesanan yang cocok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $orders->links() }}</div>
@endsection
