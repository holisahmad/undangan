@extends('layouts.app')

@section('title', 'Admin &middot; Pengguna')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="font-serif text-2xl font-semibold">Admin &middot; Pengguna</h1>
            <p class="mt-1 text-sm text-stone-600">
                {{ $users->total() }} akun terdaftar. Nonaktifkan akun untuk menghentikan akses tanpa menghapus data.
            </p>
        </div>
    </div>

    @include('admin.partials.nav')

    <form method="GET" action="{{ route('admin.users.index') }}" class="mt-6 flex flex-wrap items-end gap-3">
        <div class="w-full flex-1 sm:min-w-[18rem]">
            <label for="q" class="block text-sm font-medium">Cari nama atau email</label>
            <input id="q" name="q" type="search" value="{{ $search }}"
                   class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-rose-700 focus:outline-none focus:ring-1 focus:ring-rose-700">
        </div>
        <div>
            <label for="role" class="block text-sm font-medium">Peran</label>
            <select id="role" name="role"
                    class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-rose-700 focus:outline-none focus:ring-1 focus:ring-rose-700">
                <option value="">Semua peran</option>
                <option value="customer" @selected($role === 'customer')>Pelanggan</option>
                <option value="admin" @selected($role === 'admin')>Operator</option>
            </select>
        </div>
        <div>
            <label for="status" class="block text-sm font-medium">Status</label>
            <select id="status" name="status"
                    class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-rose-700 focus:outline-none focus:ring-1 focus:ring-rose-700">
                <option value="">Semua status</option>
                <option value="active" @selected($status === 'active')>Aktif</option>
                <option value="suspended" @selected($status === 'suspended')>Dinonaktifkan</option>
            </select>
        </div>
        <div>
            <label for="segment" class="block text-sm font-medium">Segmen</label>
            <select id="segment" name="segment"
                    class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-rose-700 focus:outline-none focus:ring-1 focus:ring-rose-700">
                <option value="">Semua segmen</option>
                @foreach ($segments as $key => $label)
                    <option value="{{ $key }}" @selected($segment === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="tag" class="block text-sm font-medium">Tag CRM</label>
            <input id="tag" name="tag" type="text" value="{{ $tag }}" list="crm-tags"
                   class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-rose-700 focus:outline-none focus:ring-1 focus:ring-rose-700">
            <datalist id="crm-tags">
                @foreach ($tagOptions as $option)
                    <option value="{{ $option }}"></option>
                @endforeach
            </datalist>
        </div>
        <button type="submit" class="rounded-md bg-stone-900 px-4 py-2 text-sm font-medium text-white hover:bg-stone-800">
            Filter
        </button>
        @if ($search !== '' || $role !== '' || $status !== '' || $tag !== '' || $segment !== '')
            <a href="{{ route('admin.users.index') }}" class="text-sm text-stone-600 underline-offset-4 hover:underline">Reset</a>
        @endif
        <a href="{{ route('admin.users.export', request()->query()) }}"
           class="rounded-md border border-stone-300 bg-white px-4 py-2 text-sm font-medium hover:border-stone-400">
            Unduh CSV
        </a>
    </form>

    <div class="mt-6 overflow-x-auto rounded-lg border border-stone-200 bg-white">
        <table class="min-w-full divide-y divide-stone-200 text-sm">
            <thead class="bg-stone-50 text-left text-xs uppercase tracking-wide text-stone-500">
                <tr>
                    <th class="px-4 py-3 font-medium">Akun</th>
                    <th class="px-4 py-3 font-medium">Peran</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Undangan</th>
                    <th class="px-4 py-3 font-medium">Pesanan</th>
                    <th class="px-4 py-3 font-medium">Tag</th>
                    <th class="px-4 py-3 font-medium">Terdaftar</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($users as $user)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ $user->name }}</p>
                            <p class="text-xs text-stone-500">{{ $user->email }}</p>
                        </td>
                        <td class="px-4 py-3">{{ $user->role->isAdmin() ? 'Operator' : 'Pelanggan' }}</td>
                        <td class="px-4 py-3">
                            @if ($user->isSuspended())
                                <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-900">Dinonaktifkan</span>
                            @else
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-900">Aktif</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-stone-700">{{ $user->weddings_count }}</td>
                        <td class="px-4 py-3 text-stone-700">{{ $user->orders_count }}</td>
                        <td class="px-4 py-3">
                            @forelse ($user->crm_tags ?? [] as $userTag)
                                <span class="rounded-full bg-stone-100 px-2 py-0.5 text-xs text-stone-700">{{ $userTag }}</span>
                            @empty
                                <span class="text-xs text-stone-400">—</span>
                            @endforelse
                        </td>
                        <td class="px-4 py-3 text-stone-500">{{ $user->created_at?->translatedFormat('d M Y') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.users.show', $user) }}"
                               class="text-rose-800 underline-offset-4 hover:underline">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-stone-500">Tidak ada akun yang cocok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
@endsection
