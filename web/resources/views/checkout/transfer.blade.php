@extends('layouts.app')

@section('title', 'Instruksi pembayaran')

@php
    $paid = $order->isPaid();
    $expired = ! $paid && $order->paymentWindowClosed();
    $pendingReview = $confirmation?->status === \App\Enums\PaymentConfirmationStatus::Submitted;
    $rejected = $confirmation?->status === \App\Enums\PaymentConfirmationStatus::Rejected;
    $canSubmit = ! $paid && ! $expired && ! $pendingReview && $accounts !== [];
    // Without a configured account the page is a dead end, so the fallback
    // offers the studio's real channels instead of a generic "contact admin".
    $supportEmail = (string) config('site.contact.email');
    $supportWhatsapp = \App\Support\Contact\PhoneNumber::whatsappLink((string) config('site.contact.whatsapp'));
@endphp

@section('content')
    <div class="mx-auto max-w-4xl space-y-6">
        <div>
            <a href="{{ route('orders.show', $order) }}" class="text-sm text-stone-500 hover:underline">&larr; Kembali ke pesanan</a>
            <h1 class="mt-2 font-serif text-2xl font-semibold sm:text-3xl">Pembayaran pesanan</h1>
            <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-stone-600">
                <span class="font-mono">{{ $order->public_order_number }}</span>
                <span class="rounded-full bg-stone-200 px-2 py-0.5 text-xs font-medium">{{ $order->displayStatusLabel() }}</span>
            </p>
        </div>

        @if ($paid)
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-4 text-sm text-emerald-900" role="status">
                <p class="font-medium">Pembayaran sudah diverifikasi.</p>
                <p class="mt-1">
                    Masa aktif undangan ditambahkan pada
                    {{ $order->paid_at?->locale(app()->getLocale())->translatedFormat('d F Y · H:i') }}.
                </p>
            </div>
        @elseif ($expired)
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-900" role="alert">
                <p class="font-medium">Batas waktu pembayaran sudah lewat.</p>
                <p class="mt-1">
                    Kode unik sebelumnya tidak berlaku lagi. Buat kode baru untuk mendapatkan nominal dan batas waktu
                    baru — jangan gunakan nominal lama.
                </p>
                <form method="POST" action="{{ route('orders.payment.regenerate', $order) }}" class="mt-3"
                      onsubmit="return confirm('Buat kode pembayaran baru? Nominal lama tidak berlaku lagi.')">
                    @csrf
                    <button type="submit"
                            class="w-full rounded-md bg-amber-800 px-4 py-3 text-sm font-medium text-white hover:bg-amber-900 sm:w-auto sm:py-2">
                        Buat kode pembayaran baru
                    </button>
                </form>
            </div>
        @else
            <div class="rounded-lg border border-rose-200 bg-white px-4 py-4">
                <p class="text-sm text-stone-600">Selesaikan transfer sebelum</p>
                <p class="mt-1 text-lg font-semibold"
                   data-countdown-to="{{ $order->payment_expires_at?->toIso8601String() }}"
                   data-countdown-expired="Waktu pembayaran habis">
                    <span data-countdown-label>{{ $order->payment_expires_at?->locale(app()->getLocale())->translatedFormat('d F Y · H:i') }}</span>
                </p>
                <p class="mt-1 text-xs text-stone-500">
                    Setelah waktu ini kode unik kedaluwarsa dan pesanan perlu kode baru.
                </p>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-5">
            <div class="space-y-6 lg:col-span-3">
                <section class="rounded-lg border border-stone-200 bg-white p-5">
                    <h2 class="font-serif text-lg font-semibold">Jumlah yang harus ditransfer</h2>
                    <p class="mt-2 text-3xl font-semibold tracking-tight">{{ $order->amountDueLabel() }}</p>
                    <p class="mt-1 text-xs text-stone-500">
                        Harus tepat sampai tiga digit terakhir. Nominal berbeda adalah penanda pesanan Anda.
                    </p>

                    <dl class="mt-4 space-y-1 border-t border-stone-100 pt-3 text-sm">
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-stone-500">Harga paket</dt>
                            <dd>Rp{{ number_format($order->total_amount, 0, ',', '.') }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-stone-500">Kode unik</dt>
                            <dd class="font-mono">+{{ $order->uniqueCodeLabel() ?? '—' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 border-t border-stone-100 pt-2 font-medium">
                            <dt>Total transfer</dt>
                            <dd>{{ $order->amountDueLabel() }}</dd>
                        </div>
                    </dl>

                    <button type="button" data-copy-text="{{ $order->amountDue() }}"
                            data-copy-label="Nominal tersalin"
                            class="mt-4 w-full rounded-md border border-stone-300 px-4 py-2.5 text-sm font-medium hover:border-stone-400 sm:w-auto sm:py-2">
                        Salin nominal
                    </button>
                </section>

                <section class="rounded-lg border border-stone-200 bg-white p-5">
                    <h2 class="font-serif text-lg font-semibold">Rekening tujuan</h2>

                    @forelse ($accounts as $account)
                        <div class="mt-3 rounded-lg border border-stone-200 bg-stone-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-stone-500">{{ $account['bank'] }}</p>
                            <p class="mt-1 break-all font-mono text-lg tracking-wide">{{ \App\Support\Payments\BankTransfer::prettyNumber($account['number']) }}</p>
                            <p class="mt-1 text-sm text-stone-600">a.n. {{ $account['holder'] }}</p>
                            <button type="button" data-copy-text="{{ $account['number'] }}"
                                    data-copy-label="Nomor rekening tersalin"
                                    class="mt-3 w-full rounded-md border border-stone-300 bg-white px-4 py-2.5 text-sm font-medium hover:border-stone-400 sm:w-auto sm:py-2">
                                Salin nomor rekening
                            </button>
                        </div>
                    @empty
                        <p class="mt-3 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="alert">
                            Rekening tujuan belum tersedia. Jangan transfer ke rekening mana pun sebelum kami
                            mengirimkan nomornya. Hubungi kami lebih dulu dengan menyebut nomor pesanan
                            <strong>{{ $order->public_order_number }}</strong>.
                        </p>
                        <div class="mt-3 flex flex-wrap gap-3">
                            @if ($supportWhatsapp !== null)
                                <a href="{{ $supportWhatsapp }}" target="_blank" rel="noopener noreferrer"
                                   class="rounded-md bg-rose-800 px-4 py-2.5 text-sm font-medium text-white hover:bg-rose-900">
                                    Tanya via WhatsApp
                                </a>
                            @endif
                            <a href="mailto:{{ $supportEmail }}"
                               class="rounded-md border border-stone-300 bg-white px-4 py-2.5 text-sm font-medium hover:border-stone-400">
                                Kirim email
                            </a>
                        </div>
                    @endforelse

                    <p class="mt-3 text-xs text-stone-500">
                        Transfer dari bank mana pun. Biaya transfer menjadi tanggungan pengirim.
                    </p>
                </section>

                <section class="rounded-lg border border-stone-200 bg-white p-5">
                    <h2 class="font-serif text-lg font-semibold">Cara membayar</h2>
                    <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-stone-700">
                        <li>Transfer <strong>{{ $order->amountDueLabel() }}</strong> ke salah satu rekening di atas.</li>
                        <li>Simpan bukti transfer dari aplikasi bank Anda.</li>
                        <li>Isi formulir konfirmasi dan unggah bukti transfer di halaman ini.</li>
                        <li>Admin mencocokkan dana masuk, lalu undangan aktif otomatis.</li>
                    </ol>
                </section>

                @if ($pendingReview)
                    <section class="rounded-lg border border-sky-200 bg-sky-50 p-5 text-sm text-sky-900" role="status">
                        <p class="font-medium">Bukti transfer sudah kami terima.</p>
                        <p class="mt-1">
                            Dikirim {{ $confirmation->created_at?->locale(app()->getLocale())->translatedFormat('d F Y · H:i') }}
                            dari {{ $confirmation->sender_bank }} a.n. {{ $confirmation->sender_name }}.
                            Verifikasi biasanya selesai pada jam kerja.
                        </p>
                    </section>
                @elseif ($rejected)
                    <section class="rounded-lg border border-rose-200 bg-rose-50 p-5 text-sm text-rose-900" role="alert">
                        <p class="font-medium">Konfirmasi sebelumnya ditolak.</p>
                        @if ($confirmation->review_note)
                            <p class="mt-1">Catatan admin: {{ $confirmation->review_note }}</p>
                        @endif
                        <p class="mt-1">Periksa nominal dan bukti transfer, lalu unggah ulang.</p>
                    </section>
                @endif

                @if ($canSubmit)
                    <section class="rounded-lg border border-stone-200 bg-white p-5">
                        <h2 class="font-serif text-lg font-semibold">Konfirmasi transfer</h2>
                        <p class="mt-1 text-sm text-stone-600">
                            Isi data sesuai bukti transfer. Berkas hanya dapat dilihat admin.
                        </p>

                        <form method="POST" action="{{ route('orders.payment.confirm', $order) }}"
                              enctype="multipart/form-data" class="mt-4 space-y-4">
                            @csrf
                            <input type="hidden" name="amount" value="{{ $order->amountDue() }}">

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="sender_name" class="block text-sm font-medium text-stone-700">Nama pengirim</label>
                                    <input id="sender_name" name="sender_name" type="text" required maxlength="120"
                                           value="{{ old('sender_name', auth()->user()->name) }}"
                                           class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2.5 text-base sm:py-2 sm:text-sm">
                                    @error('sender_name')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="sender_bank" class="block text-sm font-medium text-stone-700">Bank pengirim</label>
                                    <input id="sender_bank" name="sender_bank" type="text" required maxlength="60"
                                           value="{{ old('sender_bank') }}" placeholder="Contoh: BCA"
                                           class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2.5 text-base sm:py-2 sm:text-sm">
                                    @error('sender_bank')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div>
                                <label for="transferred_at" class="block text-sm font-medium text-stone-700">Tanggal transfer</label>
                                <input id="transferred_at" name="transferred_at" type="date" required
                                       max="{{ now()->toDateString() }}"
                                       value="{{ old('transferred_at', now()->toDateString()) }}"
                                       class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2.5 text-base sm:py-2 sm:text-sm">
                                @error('transferred_at')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="proof" class="block text-sm font-medium text-stone-700">Bukti transfer</label>
                                <input id="proof" name="proof" type="file" required
                                       accept="image/jpeg,image/png,application/pdf"
                                       class="mt-1 block w-full rounded-md border border-stone-300 bg-white px-3 py-2.5 text-base sm:py-2 sm:text-sm">
                                <p class="mt-1 text-xs text-stone-500">JPG, PNG, atau PDF. Maksimal {{ intdiv((int) config('platform.payments.bank_transfer.proof_max_kb', 4096), 1024) }} MB.</p>
                                @error('proof')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="note" class="block text-sm font-medium text-stone-700">Catatan <span class="text-stone-400">(opsional)</span></label>
                                <textarea id="note" name="note" rows="3" maxlength="500"
                                          class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2.5 text-base sm:py-2 sm:text-sm">{{ old('note') }}</textarea>
                                @error('note')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                            </div>

                            @error('amount')<p class="text-xs text-rose-700">{{ $message }}</p>@enderror

                            <button type="submit"
                                    class="w-full rounded-md bg-rose-800 px-4 py-3 text-sm font-medium text-white hover:bg-rose-900 sm:py-2">
                                Kirim konfirmasi pembayaran
                            </button>
                        </form>
                    </section>
                @endif
            </div>

            <aside class="space-y-6 lg:col-span-2">
                <section class="rounded-lg border border-stone-200 bg-white p-5">
                    <h2 class="font-serif text-lg font-semibold">Ringkasan pesanan</h2>
                    <dl class="mt-3 space-y-3 text-sm">
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-stone-500">Undangan</dt>
                            <dd class="mt-0.5 font-medium">{{ $order->wedding?->internal_title ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-stone-500">Paket</dt>
                            <dd class="mt-0.5">{{ $order->items->first()?->plan?->name ?? 'Paket' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-stone-500">Dibuat</dt>
                            <dd class="mt-0.5">{{ $order->created_at?->locale(app()->getLocale())->translatedFormat('d F Y · H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-stone-500">Batas waktu</dt>
                            <dd class="mt-0.5">{{ $order->payment_expires_at?->locale(app()->getLocale())->translatedFormat('d F Y · H:i') ?? '—' }}</dd>
                        </div>
                    </dl>

                    <a href="{{ route('orders.show', $order) }}"
                       class="mt-4 inline-block text-sm text-rose-800 underline-offset-4 hover:underline">
                        Lihat riwayat pesanan
                    </a>
                </section>

                <section class="rounded-lg border border-stone-200 bg-white p-5 text-sm text-stone-600">
                    <h2 class="font-serif text-lg font-semibold text-stone-900">Butuh bantuan?</h2>
                    <p class="mt-2">
                        Sudah transfer tetapi lupa konfirmasi? Kirim nomor pesanan
                        <span class="font-mono">{{ $order->public_order_number }}</span> ke
                        <a href="mailto:{{ config('site.contact.email') }}" class="text-rose-800 underline-offset-4 hover:underline">
                            {{ config('site.contact.email') }}
                        </a>.
                    </p>
                    <p class="mt-2 text-xs text-stone-500">{{ config('site.contact.hours') }}</p>
                </section>
            </aside>
        </div>
    </div>
@endsection
