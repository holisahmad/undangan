@extends('layouts.app')

@section('title', 'Admin &middot; ' . $order->public_order_number)

@php
    $paid = $order->isPaid();
    $expired = ! $paid && $order->paymentWindowClosed();
    $history = $order->confirmations->sortByDesc('created_at');
@endphp

@section('content')
    <a href="{{ route('admin.orders.index') }}" class="text-sm text-stone-500 hover:underline">&larr; Daftar pesanan</a>

    <div class="mt-2 flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <h1 class="font-serif text-2xl font-semibold">Pesanan {{ $order->public_order_number }}</h1>
            <p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-stone-600">
                <span class="rounded-full px-2 py-0.5 text-xs font-medium
                    {{ $paid ? 'bg-emerald-100 text-emerald-900' : ($expired ? 'bg-amber-100 text-amber-900' : 'bg-stone-200') }}">
                    {{ $order->displayStatusLabel() }}
                </span>
                <span>Dibuat {{ $order->created_at?->translatedFormat('d F Y · H:i') }}</span>
            </p>
        </div>
        @if ($order->user)
            <a href="{{ route('admin.users.show', $order->user) }}"
               class="rounded-md border border-stone-300 bg-white px-4 py-2 text-sm font-medium hover:border-stone-400">
                Lihat pemilik
            </a>
        @endif
    </div>

    @include('admin.partials.nav')

    <div class="mt-8 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            @if ($confirmation)
                <section class="rounded-lg border border-sky-200 bg-white p-5">
                    <h2 class="font-serif text-lg font-semibold">Bukti transfer menunggu verifikasi</h2>
                    <p class="mt-1 text-sm text-stone-600">
                        Cocokkan data di bawah dengan mutasi rekening sebelum menekan verifikasi.
                    </p>

                    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-stone-500">Pengirim</dt>
                            <dd class="mt-0.5 font-medium">{{ $confirmation->sender_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-stone-500">Bank pengirim</dt>
                            <dd class="mt-0.5">{{ $confirmation->sender_bank }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-stone-500">Tanggal transfer</dt>
                            <dd class="mt-0.5">{{ $confirmation->transferred_at?->translatedFormat('d F Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-stone-500">Nominal diklaim</dt>
                            <dd class="mt-0.5 font-medium">{{ $confirmation->amountLabel() }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs uppercase tracking-wide text-stone-500">Dikirim</dt>
                            <dd class="mt-0.5">
                                {{ $confirmation->created_at?->translatedFormat('d F Y · H:i') }}
                                oleh {{ $confirmation->submittedBy?->email ?? 'pemilik' }}
                            </dd>
                        </div>
                        @if ($confirmation->note)
                            <div class="sm:col-span-2">
                                <dt class="text-xs uppercase tracking-wide text-stone-500">Catatan pengirim</dt>
                                <dd class="mt-0.5 whitespace-pre-line">{{ $confirmation->note }}</dd>
                            </div>
                        @endif
                    </dl>

                    <a href="{{ route('admin.orders.proof', [$order, $confirmation]) }}"
                       class="mt-4 inline-block rounded-md border border-stone-300 bg-white px-4 py-2.5 text-sm font-medium hover:border-stone-400 sm:py-2">
                        Unduh bukti transfer
                    </a>

                    <div class="mt-5 flex flex-col gap-3 border-t border-stone-100 pt-4 sm:flex-row">
                        <form method="POST" action="{{ route('admin.orders.verify', $order) }}" class="sm:flex-1"
                              onsubmit="return confirm('Verifikasi pembayaran ini? Masa aktif undangan akan langsung ditambahkan.')">
                            @csrf
                            <button type="submit"
                                    class="w-full rounded-md bg-emerald-800 px-4 py-3 text-sm font-medium text-white hover:bg-emerald-900 sm:py-2">
                                Verifikasi dan aktifkan
                            </button>
                        </form>
                    </div>

                    <form method="POST" action="{{ route('admin.orders.reject', $order) }}" class="mt-4 space-y-3 border-t border-stone-100 pt-4">
                        @csrf
                        <div>
                            <label for="review_note" class="block text-sm font-medium text-stone-700">Tolak dengan alasan</label>
                            <textarea id="review_note" name="review_note" rows="3" required minlength="4" maxlength="500"
                                      class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2.5 text-base sm:py-2 sm:text-sm"
                                      placeholder="Contoh: dana belum masuk atau nominal tidak sesuai.">{{ old('review_note') }}</textarea>
                        </div>
                        <button type="submit"
                                class="w-full rounded-md border border-rose-300 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-900 hover:bg-rose-100 sm:w-auto sm:py-2">
                            Tolak konfirmasi
                        </button>
                    </form>
                </section>
            @endif

            <section class="rounded-lg border border-stone-200 bg-white p-5">
                <h2 class="font-serif text-lg font-semibold">Rincian tagihan</h2>
                <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-stone-500">Harga paket</dt>
                        <dd class="mt-0.5">Rp{{ number_format($order->total_amount, 0, ',', '.') }}</dd>
                    </div>
                    <div>
                        <dt class="text-stone-500">Kode unik</dt>
                        <dd class="mt-0.5 font-mono">{{ $order->uniqueCodeLabel() ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-stone-500">Total transfer</dt>
                        <dd class="mt-0.5 font-semibold">{{ $order->amountDueLabel() }}</dd>
                    </div>
                    <div>
                        <dt class="text-stone-500">Batas waktu</dt>
                        <dd class="mt-0.5">
                            {{ $order->payment_expires_at?->translatedFormat('d F Y · H:i') ?? '—' }}
                            @if ($expired)
                                <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-900">lewat</span>
                            @endif
                        </dd>
                    </div>
                </dl>

                <div class="mt-5 overflow-x-auto border-t border-stone-100 pt-4">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-xs uppercase tracking-wide text-stone-500">
                            <tr>
                                <th class="py-2 pr-3">Item</th>
                                <th class="py-2 pr-3">Durasi</th>
                                <th class="py-2 text-right">Harga</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            @foreach ($order->items as $item)
                                <tr>
                                    <td class="py-2 pr-3">{{ $item->configuration_snapshot['plan_name'] ?? $item->plan?->name ?? 'Paket' }}</td>
                                    <td class="whitespace-nowrap py-2 pr-3">{{ $item->configuration_snapshot['duration_months'] ?? $item->plan?->duration_months }} bulan</td>
                                    <td class="whitespace-nowrap py-2 text-right">Rp{{ number_format($item->total_amount, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-lg border border-stone-200 bg-white p-5">
                <h2 class="font-serif text-lg font-semibold">Riwayat konfirmasi</h2>

                @if ($history->isEmpty())
                    <p class="mt-2 text-sm text-stone-600">Belum ada bukti transfer yang dikirim.</p>
                @else
                    <ul class="mt-3 space-y-3 text-sm">
                        @foreach ($history as $row)
                            <li class="rounded-md border border-stone-100 p-3">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="font-medium">{{ $row->sender_name }} &middot; {{ $row->sender_bank }}</span>
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium
                                        {{ $row->status === \App\Enums\PaymentConfirmationStatus::Verified
                                            ? 'bg-emerald-100 text-emerald-900'
                                            : ($row->status === \App\Enums\PaymentConfirmationStatus::Rejected
                                                ? 'bg-rose-100 text-rose-900'
                                                : 'bg-sky-100 text-sky-900') }}">
                                        {{ $row->status->label() }}
                                    </span>
                                </div>
                                <p class="mt-1 text-stone-600">
                                    {{ $row->amountLabel() }} &middot; transfer {{ $row->transferred_at?->translatedFormat('d M Y') }}
                                    &middot; dikirim {{ $row->created_at?->translatedFormat('d M Y · H:i') }}
                                </p>
                                @if ($row->reviewed_at)
                                    <p class="mt-1 text-xs text-stone-500">
                                        Ditinjau {{ $row->reviewed_at->translatedFormat('d M Y · H:i') }}
                                        oleh {{ $row->reviewedBy?->email ?? 'operator' }}
                                        @if ($row->review_note)
                                            &middot; catatan: {{ $row->review_note }}
                                        @endif
                                    </p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        <aside class="space-y-6">
            <section class="rounded-lg border border-stone-200 bg-white p-5 text-sm">
                <h2 class="font-serif text-lg font-semibold">Undangan</h2>
                @if ($order->wedding)
                    <a href="{{ route('admin.weddings.show', $order->wedding) }}"
                       class="mt-2 inline-block text-rose-800 underline-offset-4 hover:underline">
                        {{ $order->wedding->internal_title }}
                    </a>
                    <p class="mt-1 text-stone-500">/u/{{ $order->wedding->slug }}</p>
                @else
                    <p class="mt-2 text-stone-500">Undangan sudah tidak ada.</p>
                @endif

                @if ($order->paid_at)
                    <p class="mt-4 border-t border-stone-100 pt-3 text-stone-600">
                        Dibayar {{ $order->paid_at->translatedFormat('d F Y · H:i') }}.
                    </p>
                @endif
            </section>

            <section class="rounded-lg border border-stone-200 bg-white p-5">
                <h2 class="font-serif text-lg font-semibold">Percobaan pembayaran</h2>
                @if ($order->payments->isEmpty())
                    <p class="mt-2 text-sm text-stone-600">Belum ada percobaan pembayaran.</p>
                @else
                    <ul class="mt-3 space-y-2 text-sm">
                        @foreach ($order->payments->sortByDesc('created_at') as $attempt)
                            <li class="border-b border-stone-100 pb-2 last:border-0">
                                <p class="font-medium">{{ $attempt->status->label() }}</p>
                                <p class="text-xs text-stone-500">
                                    {{ $attempt->provider }}
                                    @if ($attempt->provider_transaction_id)
                                        &middot; <span class="font-mono">{{ $attempt->provider_transaction_id }}</span>
                                    @endif
                                </p>
                                <p class="text-stone-600">{{ $attempt->amountLabel() }}</p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="rounded-lg border border-stone-200 bg-white p-5 text-sm text-stone-600">
                <h2 class="font-serif text-lg font-semibold text-stone-900">Catatan</h2>
                <p class="mt-2">
                    Verifikasi hanya boleh dilakukan setelah dana benar-benar masuk.
                    Setelah diverifikasi, masa aktif undangan langsung bertambah dan pemilik menerima email.
                </p>
            </section>
        </aside>
    </div>
@endsection
