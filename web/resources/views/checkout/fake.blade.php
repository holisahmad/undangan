@extends('layouts.app')

@section('title', 'Checkout sandbox')

@section('content')
    <div class="mx-auto max-w-xl">
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            Halaman ini adalah penyedia pembayaran simulasi. Tidak ada uang yang dipindahkan dan tidak ada
            data kartu yang diminta.
        </div>

        <div class="mt-6 rounded-lg border border-stone-200 bg-white p-6">
            <p class="text-sm text-stone-500">Pesanan</p>
            <p class="font-mono text-sm">{{ $order->public_order_number }}</p>

            <p class="mt-4 text-sm text-stone-500">Undangan</p>
            <p class="font-medium">{{ $order->wedding?->internal_title }}</p>

            <p class="mt-4 text-sm text-stone-500">Jumlah</p>
            <p class="text-2xl font-semibold">Rp{{ number_format($order->total_amount, 0, ',', '.') }}</p>

            <div class="mt-6 space-y-3">
                <form method="POST" action="{{ route('checkout.fake.complete', $order) }}">
                    @csrf
                    <input type="hidden" name="status" value="succeeded">
                    <button type="submit"
                            class="w-full rounded-md bg-rose-800 px-4 py-2 text-sm font-medium text-white hover:bg-rose-900">
                        Simulasikan pembayaran berhasil
                    </button>
                </form>

                <form method="POST" action="{{ route('checkout.fake.complete', $order) }}">
                    @csrf
                    <input type="hidden" name="status" value="failed">
                    <button type="submit"
                            class="w-full rounded-md border border-stone-300 bg-white px-4 py-2 text-sm font-medium hover:border-stone-400">
                        Simulasikan pembayaran gagal
                    </button>
                </form>
            </div>

            <p class="mt-4 text-xs text-stone-500">
                Kedua aksi mengirim notifikasi bertanda tangan ke endpoint webhook aplikasi, persis seperti
                penyedia sungguhan. Status pesanan hanya berubah setelah notifikasi diverifikasi.
            </p>
        </div>

        <a href="{{ route('orders.show', $order) }}" class="mt-4 inline-block text-sm text-stone-600 hover:underline">
            &larr; Kembali ke pesanan
        </a>
    </div>
@endsection
