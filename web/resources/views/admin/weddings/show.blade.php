@extends('layouts.app')

@section('title', 'Admin &middot; ' . $wedding->internal_title)

@section('content')
    <a href="{{ route('admin.weddings.index') }}" class="text-sm text-stone-500 hover:underline">&larr; Daftar undangan</a>

    <div class="mt-2 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="font-serif text-2xl font-semibold">{{ $wedding->internal_title }}</h1>
            <p class="mt-1 text-sm text-stone-600">
                /u/{{ $wedding->slug }} &middot; {{ $wedding->lifecycle_status->label() }}
            </p>
        </div>
        @if ($wedding->activePublication)
            <a href="{{ route('invitations.public', ['slug' => $wedding->slug]) }}" target="_blank" rel="noopener noreferrer"
               class="rounded-md border border-stone-300 bg-white px-4 py-2 text-sm font-medium hover:border-stone-400">
                Buka halaman publik
            </a>
        @endif
    </div>

    @include('admin.partials.nav')

    <div class="mt-8 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="rounded-lg border border-stone-200 bg-white p-5">
                <h2 class="font-serif text-lg font-semibold">Ringkasan</h2>
                <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-stone-500">Pemilik</dt>
                        <dd class="mt-0.5">{{ $wedding->owner?->name }} &middot; {{ $wedding->owner?->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-stone-500">Dibuat</dt>
                        <dd class="mt-0.5">{{ $wedding->created_at?->translatedFormat('d F Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-stone-500">Mempelai</dt>
                        <dd class="mt-0.5">{{ $wedding->partners->count() }} orang</dd>
                    </div>
                    <div>
                        <dt class="text-stone-500">Acara</dt>
                        <dd class="mt-0.5">{{ $wedding->events->count() }} acara</dd>
                    </div>
                    <div>
                        <dt class="text-stone-500">Publikasi terakhir</dt>
                        <dd class="mt-0.5">
                            @if ($wedding->latestPublication)
                                Versi {{ $wedding->latestPublication->version_number }}
                                &middot; {{ $wedding->latestPublication->published_at?->translatedFormat('d M Y H:i') }}
                                @if ($wedding->latestPublication->unpublished_at)
                                    <span class="text-stone-500">(dinonaktifkan)</span>
                                @endif
                            @else
                                Belum pernah terbit
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-stone-500">Masa aktif</dt>
                        <dd class="mt-0.5">
                            {{ $entitlement?->expires_at?->translatedFormat('d F Y') ?? 'Tidak ada entitlement aktif' }}
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-lg border border-stone-200 bg-white p-5">
                <h2 class="font-serif text-lg font-semibold">Audit log undangan ini</h2>
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
            <section class="rounded-lg border border-rose-200 bg-white p-5">
                <h2 class="font-serif text-lg font-semibold">Nonaktifkan publikasi</h2>
                <p class="mt-2 text-sm text-stone-600">
                    Tautan publik langsung berhenti melayani tamu. Data pemilik tidak diubah.
                </p>
                <form method="POST" action="{{ route('admin.weddings.suspend', $wedding) }}" class="mt-3 space-y-3"
                      onsubmit="return confirm('Nonaktifkan publikasi undangan ini?')">
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

            <section class="rounded-lg border border-stone-200 bg-white p-5 text-sm text-stone-600">
                <h2 class="font-serif text-lg font-semibold text-stone-900">Catatan</h2>
                <p class="mt-2">
                    Operator tidak dapat mengubah isi draf atau data mempelai pemilik.
                    Perubahan konten tetap hanya lewat akun pemilik.
                </p>
            </section>
        </aside>
    </div>
@endsection
