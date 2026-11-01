@extends('layouts.app')

@section('title', 'Admin undangan')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="font-serif text-2xl font-semibold">Admin &middot; Undangan</h1>
            <p class="mt-1 text-sm text-stone-600">
                {{ $weddings->total() }} undangan terdaftar. Akses ini hanya untuk operator.
            </p>
        </div>
        <a href="{{ route('admin.users.index') }}"
           class="rounded-md border border-stone-300 bg-white px-4 py-2 text-sm font-medium hover:border-stone-400">
            Pengguna
        </a>
    </div>

    @include('admin.partials.nav')

    <form method="GET" action="{{ route('admin.weddings.index') }}" class="mt-6 flex flex-wrap items-end gap-3">
        <div class="w-full flex-1 sm:min-w-[18rem]">
            <label for="q" class="block text-sm font-medium">Cari judul, slug, atau email pemilik</label>
            <input id="q" name="q" type="search" value="{{ $search }}"
                   class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-rose-700 focus:outline-none focus:ring-1 focus:ring-rose-700">
        </div>
        <button type="submit" class="rounded-md bg-stone-900 px-4 py-2 text-sm font-medium text-white hover:bg-stone-800">
            Cari
        </button>
        @if ($search !== '')
            <a href="{{ route('admin.weddings.index') }}" class="text-sm text-stone-600 underline-offset-4 hover:underline">Reset</a>
        @endif
    </form>

    <div class="mt-6 overflow-x-auto rounded-lg border border-stone-200 bg-white">
        <table class="min-w-full divide-y divide-stone-200 text-sm">
            <thead class="bg-stone-50 text-left text-xs uppercase tracking-wide text-stone-500">
                <tr>
                    <th class="px-4 py-3 font-medium">Undangan</th>
                    <th class="px-4 py-3 font-medium">Pemilik</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Publikasi aktif</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($weddings as $wedding)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ $wedding->internal_title }}</p>
                            <p class="text-xs text-stone-500">/u/{{ $wedding->slug }}</p>
                        </td>
                        <td class="px-4 py-3 text-stone-700">{{ $wedding->owner?->email ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $wedding->lifecycle_status->label() }}</td>
                        <td class="px-4 py-3 text-stone-700">
                            @if ($wedding->activePublication)
                                Versi {{ $wedding->activePublication->version_number }}
                                @if ($wedding->activePublication->expires_at)
                                    &middot; s.d. {{ $wedding->activePublication->expires_at->translatedFormat('d M Y') }}
                                @endif
                            @else
                                <span class="text-stone-500">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.weddings.show', $wedding) }}"
                               class="text-rose-800 underline-offset-4 hover:underline">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-stone-500">Tidak ada undangan yang cocok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $weddings->links() }}</div>
@endsection
