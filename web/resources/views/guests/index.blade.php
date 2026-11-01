@extends('layouts.app')

@section('title', 'Tamu &middot; '.$wedding->internal_title)

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="{{ route('weddings.show', $wedding) }}" class="text-sm text-stone-500 hover:underline">
                &larr; {{ $wedding->internal_title }}
            </a>
            <h1 class="mt-2 font-serif text-2xl font-semibold">Tamu &amp; RSVP</h1>
            <p class="mt-1 text-sm text-stone-600">
                Kelola daftar tamu, tautan personal, dan respons kehadiran. Nomor telepon dan email tidak pernah
                ditampilkan di halaman publik. Pengingat RSVP hanya dikirim ke tamu yang punya email, belum menjawab,
                dan belum diingatkan dalam {{ config('platform.guests.reminder_cooldown_days') }} hari terakhir.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($wedding->activePublication !== null)
                <form method="POST" action="{{ route('weddings.guests.remind', $wedding) }}"
                      onsubmit="return confirm('Kirim pengingat RSVP ke tamu yang punya email dan belum menjawab?')">
                    @csrf
                    <button type="submit"
                            class="rounded-md border border-stone-300 bg-white px-4 py-2 text-sm font-medium hover:border-stone-400">
                        Kirim pengingat
                    </button>
                </form>
            @endif
            <a href="{{ route('weddings.guests.export', $wedding) }}"
               class="rounded-md border border-stone-300 bg-white px-4 py-2 text-sm font-medium hover:border-stone-400">
                Ekspor CSV
            </a>
            <a href="{{ route('weddings.guests.create', $wedding) }}"
               class="rounded-md bg-rose-800 px-4 py-2 text-sm font-medium text-white hover:bg-rose-900">
                Tambah tamu
            </a>
        </div>
    </div>

    @if (session('guest_link'))
        <div class="mt-6 rounded-md border border-emerald-200 bg-emerald-50 p-4">
            <p class="text-sm font-medium text-emerald-900">Tautan personal (hanya ditampilkan sekali)</p>
            <input type="text" readonly value="{{ session('guest_link') }}" onclick="this.select()"
                   class="mt-2 w-full rounded border border-emerald-300 bg-white px-3 py-2 font-mono text-xs">
            <p class="mt-1 text-xs text-emerald-800">
                Simpan atau bagikan sekarang. Tautan disimpan dalam bentuk hash, jadi tidak dapat ditampilkan
                lagi. Membuat tautan baru otomatis mencabut tautan sebelumnya.
            </p>
        </div>
    @endif

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border border-stone-200 bg-white p-4">
            <p class="text-xs uppercase tracking-wide text-stone-500">Total tamu</p>
            <p class="mt-1 text-2xl font-semibold">{{ $stats['total'] }}</p>
            <p class="text-xs text-stone-500">
                @if ($quotaLimit === null)
                    Tanpa batas kuota
                @else
                    Kuota paket {{ $quotaLimit }} tamu
                @endif
            </p>
        </div>
        <div class="rounded-lg border border-stone-200 bg-white p-4">
            <p class="text-xs uppercase tracking-wide text-stone-500">Tautan aktif</p>
            <p class="mt-1 text-2xl font-semibold">{{ $stats['linked'] }}</p>
            <p class="text-xs text-stone-500">Dari {{ $stats['total'] }} tamu</p>
        </div>
        <div class="rounded-lg border border-stone-200 bg-white p-4">
            <p class="text-xs uppercase tracking-wide text-stone-500">Sudah RSVP</p>
            <p class="mt-1 text-2xl font-semibold">{{ $stats['responded'] }}</p>
            <p class="text-xs text-stone-500">
                {{ $stats['total'] > 0 ? round($stats['responded'] / $stats['total'] * 100) : 0 }}% dari daftar
            </p>
        </div>
        <div class="rounded-lg border border-stone-200 bg-white p-4">
            <p class="text-xs uppercase tracking-wide text-stone-500">Perkiraan hadir</p>
            <p class="mt-1 text-2xl font-semibold">{{ $stats['attending'] }}</p>
            <p class="text-xs text-stone-500">Total peserta yang menyatakan hadir</p>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-3">
        <section class="rounded-lg border border-stone-200 bg-white p-5 lg:col-span-2">
            <h2 class="font-serif text-lg font-semibold">Impor CSV</h2>
            <p class="mt-1 text-sm text-stone-600">
                Kolom yang dikenali: <code class="rounded bg-stone-100 px-1">name</code> (wajib),
                <code class="rounded bg-stone-100 px-1">group</code>,
                <code class="rounded bg-stone-100 px-1">phone</code>,
                <code class="rounded bg-stone-100 px-1">email</code>,
                <code class="rounded bg-stone-100 px-1">allowed_party_size</code>
                (1&ndash;{{ config('platform.guests.max_party_size') }}). Maksimal
                {{ config('platform.guests.import_max_rows') }} baris per berkas.
                Pemisah koma, titik koma, atau tab semuanya diterima; simpan berkas sebagai CSV UTF-8.
            </p>
            <div class="mt-3 flex flex-wrap items-center gap-2 text-sm">
                <a href="{{ route('weddings.guests.template', $wedding) }}"
                   class="font-medium text-rose-800 underline-offset-2 hover:underline">
                    Unduh template CSV
                </a>
                <span class="text-xs text-stone-500">Sudah berisi header dan 2 contoh baris yang bisa langsung diubah.</span>
            </div>
            <form method="POST" action="{{ route('weddings.guests.import', $wedding) }}"
                  enctype="multipart/form-data" class="mt-3 flex flex-wrap items-center gap-3">
                @csrf
                <input type="file" name="file" accept=".csv,.txt,text/csv,text/plain" required
                       class="rounded-md border border-stone-300 bg-white px-3 py-2 text-sm">
                <button type="submit"
                        class="rounded-md border border-stone-300 bg-white px-4 py-2 text-sm font-medium hover:border-stone-400">
                    Impor
                </button>
            </form>
            <p class="mt-2 text-xs text-stone-500">
                Impor bersifat semua-atau-tidak-sama-sekali: bila ada baris bermasalah, tidak ada tamu yang
                disimpan dan setiap masalah ditampilkan beserta nomor barisnya.
            </p>
        </section>

        <section class="rounded-lg border border-stone-200 bg-white p-5">
            <h2 class="font-serif text-lg font-semibold">Grup tamu</h2>
            @if ($groups->isEmpty())
                <p class="mt-1 text-sm text-stone-500">Belum ada grup.</p>
            @else
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($groups as $group)
                        <li class="flex items-center justify-between gap-2">
                            <a href="{{ route('weddings.guests.index', ['wedding' => $wedding, 'group' => $group->getKey()]) }}"
                               class="text-stone-700 hover:underline">
                                {{ $group->name }}
                            </a>
                            <span class="flex items-center gap-2 text-xs text-stone-500">
                                {{ $group->guests_count }}
                                <form method="POST"
                                      action="{{ route('weddings.guest-groups.destroy', [$wedding, $group]) }}"
                                      onsubmit="return confirm('Hapus grup ini? Tamu di dalamnya tetap tersimpan tanpa grup.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-700 hover:underline">hapus</button>
                                </form>
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif

            <form method="POST" action="{{ route('weddings.guest-groups.store', $wedding) }}" class="mt-4 flex gap-2">
                @csrf
                <input type="text" name="name" required maxlength="60" placeholder="Nama grup baru"
                       class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
                <button type="submit"
                        class="shrink-0 rounded-md border border-stone-300 bg-white px-3 py-2 text-sm font-medium hover:border-stone-400">
                    Tambah
                </button>
            </form>
        </section>
    </div>

    <section class="mt-8">
        <form method="GET" action="{{ route('weddings.guests.index', $wedding) }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="q" class="block text-xs font-medium text-stone-600">Cari nama</label>
                <input id="q" name="q" type="search" value="{{ $search }}"
                       class="mt-1 rounded-md border border-stone-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label for="group" class="block text-xs font-medium text-stone-600">Grup</label>
                <select id="group" name="group" class="mt-1 rounded-md border border-stone-300 px-3 py-2 text-sm">
                    <option value="">Semua grup</option>
                    @foreach ($groups as $group)
                        <option value="{{ $group->getKey() }}" @selected($groupId === $group->getKey())>
                            {{ $group->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded-md bg-stone-800 px-4 py-2 text-sm font-medium text-white hover:bg-stone-900">
                Terapkan
            </button>
            @if ($search !== '' || $groupId !== '')
                <a href="{{ route('weddings.guests.index', $wedding) }}" class="text-sm text-stone-600 hover:underline">
                    Reset
                </a>
            @endif
        </form>

        <div class="mt-4 overflow-x-auto rounded-lg border border-stone-200 bg-white">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs uppercase tracking-wide text-stone-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">Nama</th>
                        <th class="px-4 py-3 font-medium">Grup</th>
                        <th class="px-4 py-3 font-medium">Kuota</th>
                        <th class="px-4 py-3 font-medium">RSVP</th>
                        <th class="px-4 py-3 font-medium">Tautan</th>
                        <th class="px-4 py-3 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($guests as $guest)
                        @php
                            $rsvp = $guest->rsvp;
                            $activeLink = $guest->activeInvitationLink;
                            $latestLink = $guest->latestInvitationLink;
                            $link = $activeLink ?? $latestLink;
                        @endphp
                        <tr>
                            <td class="px-4 py-3">
                                <span class="font-medium">{{ $guest->display_name }}</span>
                                @if ($guest->contact_phone)
                                    <span class="block text-xs text-stone-500">{{ $guest->contact_phone }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-stone-600">{{ $guest->group?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-stone-600">{{ $guest->allowed_party_size }} orang</td>
                            <td class="px-4 py-3">
                                @if ($rsvp === null)
                                    <span class="text-xs text-stone-400">Belum merespons</span>
                                @else
                                    <span class="rounded-full bg-stone-100 px-2 py-0.5 text-xs font-medium text-stone-700">
                                        {{ $rsvp->status->label() }}
                                    </span>
                                    @if ($rsvp->status === \App\Enums\RsvpStatus::Attending)
                                        <span class="block text-xs text-stone-500">{{ $rsvp->party_size }} orang</span>
                                    @endif
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($link === null)
                                    <span class="text-xs text-stone-400">Belum dibuat</span>
                                @elseif ($activeLink !== null)
                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800">
                                        Aktif
                                    </span>
                                    <span class="block text-xs text-stone-500">{{ $link->view_count }}&times; dibuka</span>
                                @elseif ($link->isRevoked())
                                    <span class="text-xs text-stone-500">Dicabut</span>
                                @else
                                    <span class="text-xs text-amber-700">Kedaluwarsa</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center justify-end gap-3 text-xs">
                                    <a href="{{ route('weddings.guests.edit', [$wedding, $guest]) }}"
                                       class="text-stone-700 hover:underline">Edit</a>

                                    @if ($activeLink !== null)
                                        <form method="POST"
                                              action="{{ route('weddings.guests.link.destroy', [$wedding, $guest]) }}"
                                              onsubmit="return confirm('Cabut tautan personal tamu ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-stone-700 hover:underline">Cabut tautan</button>
                                        </form>
                                    @else
                                        <form method="POST"
                                              action="{{ route('weddings.guests.link.store', [$wedding, $guest]) }}">
                                            @csrf
                                            <button type="submit" class="text-rose-800 hover:underline">Buat tautan</button>
                                        </form>
                                    @endif

                                    <form method="POST"
                                          action="{{ route('weddings.guests.destroy', [$wedding, $guest]) }}"
                                          onsubmit="return confirm('Hapus tamu ini beserta RSVP dan tautannya?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose-700 hover:underline">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-stone-500">
                                Belum ada tamu yang cocok.
                                <a href="{{ route('weddings.guests.create', $wedding) }}" class="text-rose-800 hover:underline">
                                    Tambah tamu pertama
                                </a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $guests->links() }}</div>
    </section>
@endsection
