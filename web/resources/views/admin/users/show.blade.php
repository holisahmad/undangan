@extends('layouts.app')

@section('title', 'Admin &middot; ' . $user->name)

@section('content')
    <a href="{{ route('admin.users.index') }}" class="text-sm text-stone-500 hover:underline">&larr; Daftar pengguna</a>

    <div class="mt-2 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="font-serif text-2xl font-semibold">{{ $user->name }}</h1>
            <p class="mt-1 text-sm text-stone-600">
                {{ $user->email }} &middot; {{ $user->role->isAdmin() ? 'Operator' : 'Pelanggan' }}
            </p>
        </div>
        @if ($user->isSuspended())
            <span class="rounded-full bg-rose-100 px-3 py-1 text-sm font-medium text-rose-900">Dinonaktifkan</span>
        @else
            <span class="rounded-full bg-emerald-100 px-3 py-1 text-sm font-medium text-emerald-900">Aktif</span>
        @endif
    </div>

    @include('admin.partials.nav')

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="rounded-lg border border-stone-200 bg-white p-5">
                <h2 class="font-serif text-lg font-semibold">Ringkasan akun</h2>
                <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-stone-500">Terdaftar</dt>
                        <dd class="mt-0.5">{{ $user->created_at?->translatedFormat('d F Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-stone-500">Undangan</dt>
                        <dd class="mt-0.5">{{ $user->weddings_count }} undangan</dd>
                    </div>
                    <div>
                        <dt class="text-stone-500">Pesanan</dt>
                        <dd class="mt-0.5">{{ $user->orders_count }} pesanan</dd>
                    </div>
                    @if ($user->isSuspended())
                        <div>
                            <dt class="text-stone-500">Dinonaktifkan sejak</dt>
                            <dd class="mt-0.5">{{ $user->suspended_at?->translatedFormat('d F Y H:i') }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-stone-500">Alasan</dt>
                            <dd class="mt-0.5">{{ $user->suspension_reason ?? '—' }}</dd>
                        </div>
                    @endif
                </dl>
            </section>

            <section class="rounded-lg border border-stone-200 bg-white p-5">
                <h2 class="font-serif text-lg font-semibold">CRM</h2>
                <p class="mt-1 text-sm text-stone-600">
                    Catatan internal dan tag untuk kampanye. Hanya terlihat oleh operator, tidak pernah
                    ditampilkan ke pelanggan.
                </p>

                <form method="POST" action="{{ route('admin.users.crm.update', $user) }}" class="mt-3 space-y-3">
                    @csrf
                    <div>
                        <label for="crm_tags" class="block text-sm font-medium">Tag (pisahkan dengan koma)</label>
                        <input id="crm_tags" name="crm_tags" type="text" list="crm-tags"
                               value="{{ old('crm_tags', implode(', ', $user->crm_tags ?? [])) }}"
                               class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-rose-700 focus:outline-none focus:ring-1 focus:ring-rose-700">
                        <datalist id="crm-tags">
                            @foreach ($tagOptions as $option)
                                <option value="{{ $option }}"></option>
                            @endforeach
                        </datalist>
                        <p class="mt-1 text-xs text-stone-500">Maksimal 10 tag, masing-masing 24 karakter.</p>
                    </div>
                    <div>
                        <label for="crm_note" class="block text-sm font-medium">Catatan</label>
                        <textarea id="crm_note" name="crm_note" rows="3" maxlength="1000"
                                  class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-rose-700 focus:outline-none focus:ring-1 focus:ring-rose-700">{{ old('crm_note', $user->crm_note) }}</textarea>
                    </div>
                    <button type="submit"
                            class="rounded-md bg-stone-900 px-4 py-2 text-sm font-medium text-white hover:bg-stone-800">
                        Simpan CRM
                    </button>
                </form>
            </section>

            <section class="rounded-lg border border-stone-200 bg-white p-5">
                <h2 class="font-serif text-lg font-semibold">Testimoni</h2>
                @if ($user->testimonial)
                    @php($testimonial = $user->testimonial)
                    <p class="mt-2 text-sm text-amber-600" aria-label="Penilaian {{ $testimonial->rating }} dari 5">
                        @for ($star = 1; $star <= 5; $star++)
                            <span aria-hidden="true">{{ $star <= $testimonial->rating ? '★' : '☆' }}</span>
                        @endfor
                    </p>
                    <p class="mt-2 whitespace-pre-line text-sm text-stone-700">{{ $testimonial->quote }}</p>
                    <p class="mt-2 text-xs text-stone-500">
                        {{ $testimonial->wedding?->internal_title ?? 'Undangan' }} &middot;
                        status: {{ $testimonial->status }}
                        @if ($testimonial->moderated_at)
                            &middot; ditinjau {{ $testimonial->moderated_at->translatedFormat('d M Y') }}
                        @endif
                    </p>
                    <a href="{{ route('admin.testimonials.index') }}" class="mt-2 inline-block text-sm text-rose-800 hover:underline">
                        Buka antrean moderasi
                    </a>
                @else
                    <p class="mt-2 text-sm text-stone-600">Pelanggan ini belum mengirim testimoni.</p>
                @endif
            </section>

            <section class="rounded-lg border border-stone-200 bg-white p-5">
                <h2 class="font-serif text-lg font-semibold">Undangan terakhir</h2>
                @if ($weddings->isEmpty())
                    <p class="mt-2 text-sm text-stone-600">Belum ada undangan.</p>
                @else
                    <div class="mt-3 overflow-x-auto">
                        <table class="min-w-full divide-y divide-stone-200 text-sm">
                            <thead class="text-left text-xs uppercase tracking-wide text-stone-500">
                                <tr>
                                    <th class="py-2 pr-4 font-medium">Judul</th>
                                    <th class="py-2 pr-4 font-medium">Status</th>
                                    <th class="py-2 pr-4 font-medium">Dibuat</th>
                                    <th class="py-2 font-medium"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100">
                                @foreach ($weddings as $wedding)
                                    <tr>
                                        <td class="py-2 pr-4">
                                            <p class="font-medium">{{ $wedding->internal_title }}</p>
                                            <p class="text-xs text-stone-500">/u/{{ $wedding->slug }}</p>
                                        </td>
                                        <td class="py-2 pr-4">{{ $wedding->lifecycle_status->label() }}</td>
                                        <td class="py-2 pr-4 text-stone-600">{{ $wedding->created_at?->translatedFormat('d M Y') }}</td>
                                        <td class="py-2 text-right">
                                            <a href="{{ route('admin.weddings.show', $wedding) }}"
                                               class="text-rose-800 underline-offset-4 hover:underline">Detail</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="rounded-lg border border-stone-200 bg-white p-5">
                <h2 class="font-serif text-lg font-semibold">Pesanan terakhir</h2>
                @if ($orders->isEmpty())
                    <p class="mt-2 text-sm text-stone-600">Belum ada pesanan.</p>
                @else
                    <div class="mt-3 overflow-x-auto">
                        <table class="min-w-full divide-y divide-stone-200 text-sm">
                            <thead class="text-left text-xs uppercase tracking-wide text-stone-500">
                                <tr>
                                    <th class="py-2 pr-4 font-medium">Nomor</th>
                                    <th class="py-2 pr-4 font-medium">Paket</th>
                                    <th class="py-2 pr-4 font-medium">Total</th>
                                    <th class="py-2 pr-4 font-medium">Status</th>
                                    <th class="py-2 font-medium">Dibuat</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100">
                                @foreach ($orders as $order)
                                    <tr>
                                        <td class="py-2 pr-4 font-mono text-xs">{{ $order->public_order_number }}</td>
                                        <td class="py-2 pr-4">{{ $order->items->first()?->plan?->name ?? '—' }}</td>
                                        <td class="py-2 pr-4 font-medium">Rp{{ number_format($order->total_amount, 0, ',', '.') }}</td>
                                        <td class="py-2 pr-4">{{ $order->status->label() }}</td>
                                        <td class="py-2 text-stone-600">{{ $order->created_at?->translatedFormat('d M Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="rounded-lg border border-stone-200 bg-white p-5">
                <h2 class="font-serif text-lg font-semibold">Audit log akun ini</h2>
                @if ($auditLogs->isEmpty())
                    <p class="mt-2 text-sm text-stone-600">Belum ada catatan.</p>
                @else
                    <div class="mt-3 overflow-x-auto">
                        <table class="min-w-full divide-y divide-stone-200 text-sm">
                            <thead class="text-left text-xs uppercase tracking-wide text-stone-500">
                                <tr>
                                    <th class="py-2 pr-4 font-medium">Waktu</th>
                                    <th class="py-2 pr-4 font-medium">Aksi</th>
                                    <th class="py-2 pr-4 font-medium">Aktor</th>
                                    <th class="py-2 font-medium">Metadata</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100">
                                @foreach ($auditLogs as $log)
                                    <tr>
                                        <td class="py-2 pr-4 whitespace-nowrap text-stone-600">{{ $log->created_at?->translatedFormat('d M Y H:i') }}</td>
                                        <td class="py-2 pr-4 font-medium">{{ $log->action }}</td>
                                        <td class="py-2 pr-4 text-stone-600">{{ $log->actor?->email ?? 'sistem' }}</td>
                                        <td class="py-2 text-xs text-stone-600">
                                            {{ $log->metadata ? json_encode($log->metadata, JSON_UNESCAPED_UNICODE) : '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>

        <aside class="space-y-6">
            @if ($user->is(auth()->user()) || $user->isAdmin())
                <section class="rounded-lg border border-stone-200 bg-white p-5 text-sm text-stone-600">
                    <h2 class="font-serif text-lg font-semibold text-stone-900">Tidak dapat dinonaktifkan</h2>
                    <p class="mt-2">
                        Akun operator dan akun sendiri tidak dapat dinonaktifkan dari panel ini.
                        Gunakan proses internal agar tidak mengunci akses operator.
                    </p>
                </section>
            @elseif ($user->isSuspended())
                <section class="rounded-lg border border-emerald-200 bg-white p-5">
                    <h2 class="font-serif text-lg font-semibold">Aktifkan kembali</h2>
                    <p class="mt-2 text-sm text-stone-600">
                        Pemilik dapat masuk lagi setelah aktivasi. Data undangan dan pesanan tidak berubah.
                    </p>
                    <form method="POST" action="{{ route('admin.users.activate', $user) }}" class="mt-3"
                          onsubmit="return confirm('Aktifkan kembali akun ini?')">
                        @csrf
                        <button type="submit" class="w-full rounded-md bg-emerald-800 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-900">
                            Aktifkan akun
                        </button>
                    </form>
                </section>
            @else
                <section class="rounded-lg border border-rose-200 bg-white p-5">
                    <h2 class="font-serif text-lg font-semibold">Nonaktifkan akun</h2>
                    <p class="mt-2 text-sm text-stone-600">
                        Login berikutnya ditolak dan sesi aktif berakhir. Undangan yang sudah terbit tetap dapat dibuka tamu
                        sampai operator menonaktifkan publikasinya.
                    </p>
                    <form method="POST" action="{{ route('admin.users.suspend', $user) }}" class="mt-3 space-y-3"
                          onsubmit="return confirm('Nonaktifkan akun ini?')">
                        @csrf
                        <div>
                            <label for="reason" class="block text-sm font-medium">Alasan (tercatat di audit log)</label>
                            <textarea id="reason" name="reason" rows="3" required minlength="4" maxlength="200"
                                      class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-rose-700 focus:outline-none focus:ring-1 focus:ring-rose-700"></textarea>
                        </div>
                        <button type="submit" class="w-full rounded-md bg-rose-800 px-4 py-2 text-sm font-medium text-white hover:bg-rose-900">
                            Nonaktifkan
                        </button>
                    </form>
                </section>
            @endif

            <section class="rounded-lg border border-stone-200 bg-white p-5 text-sm text-stone-600">
                <h2 class="font-serif text-lg font-semibold text-stone-900">Catatan</h2>
                <p class="mt-2">
                    Suspensi tidak menghapus akun atau data. Operator hanya mengubah status akses,
                    dan setiap perubahan tercatat di audit log.
                </p>
            </section>
        </aside>
    </div>
@endsection
