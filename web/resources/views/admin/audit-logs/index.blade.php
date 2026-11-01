@extends('layouts.app')

@section('title', 'Audit log')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="font-serif text-2xl font-semibold">Audit log</h1>
            <p class="mt-1 text-sm text-stone-600">{{ $logs->total() }} catatan tindakan sensitif.</p>
        </div>
    </div>

    @include('admin.partials.nav')

    <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="mt-6 flex flex-wrap items-end gap-3">
        <div class="w-full sm:w-auto sm:min-w-[14rem]">
            <label for="action" class="block text-sm font-medium">Aksi</label>
            <select id="action" name="action"
                    class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-rose-700 focus:outline-none focus:ring-1 focus:ring-rose-700">
                <option value="">Semua aksi</option>
                @foreach ($actions as $option)
                    <option value="{{ $option }}" @selected($action === $option)>{{ $option }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-full sm:w-auto sm:min-w-[12rem]">
            <label for="resource_type" class="block text-sm font-medium">Resource</label>
            <select id="resource_type" name="resource_type"
                    class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-rose-700 focus:outline-none focus:ring-1 focus:ring-rose-700">
                <option value="">Semua resource</option>
                @foreach ($resourceTypes as $option)
                    <option value="{{ $option }}" @selected($resourceType === $option)>{{ $option }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-full sm:w-auto sm:min-w-[14rem]">
            <label for="actor" class="block text-sm font-medium">Aktor</label>
            <input id="actor" name="actor" type="search" value="{{ $actor }}" placeholder="Nama atau email"
                   class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-rose-700 focus:outline-none focus:ring-1 focus:ring-rose-700">
        </div>
        <div>
            <label for="from" class="block text-sm font-medium">Dari tanggal</label>
            <input id="from" name="from" type="date" value="{{ $from }}"
                   class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-rose-700 focus:outline-none focus:ring-1 focus:ring-rose-700">
        </div>
        <div>
            <label for="to" class="block text-sm font-medium">Sampai tanggal</label>
            <input id="to" name="to" type="date" value="{{ $to }}"
                   class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-rose-700 focus:outline-none focus:ring-1 focus:ring-rose-700">
        </div>
        <button type="submit" class="rounded-md bg-stone-900 px-4 py-2 text-sm font-medium text-white hover:bg-stone-800">
            Filter
        </button>
        @if ($action !== '' || $resourceType !== '' || $actor !== '' || $from !== '' || $to !== '')
            <a href="{{ route('admin.audit-logs.index') }}" class="text-sm text-stone-600 underline-offset-4 hover:underline">Reset</a>
        @endif
    </form>

    <div class="mt-6 overflow-x-auto rounded-lg border border-stone-200 bg-white">
        <table class="min-w-full divide-y divide-stone-200 text-sm">
            <thead class="bg-stone-50 text-left text-xs uppercase tracking-wide text-stone-500">
                <tr>
                    <th class="px-4 py-3 font-medium">Waktu</th>
                    <th class="px-4 py-3 font-medium">Aksi</th>
                    <th class="px-4 py-3 font-medium">Resource</th>
                    <th class="px-4 py-3 font-medium">Aktor</th>
                    <th class="px-4 py-3 font-medium">Hasil</th>
                    <th class="px-4 py-3 font-medium">Metadata</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($logs as $log)
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap text-stone-600">{{ $log->created_at?->translatedFormat('d M Y H:i') }}</td>
                        <td class="px-4 py-3 font-medium">{{ $log->action }}</td>
                        <td class="px-4 py-3 text-stone-600">
                            {{ $log->resource_type }}
                            @if ($log->resource_id)
                                <span class="text-xs">{{ \Illuminate\Support\Str::limit($log->resource_id, 8, '…') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-stone-600">{{ $log->actor?->email ?? 'sistem' }}</td>
                        <td class="px-4 py-3">{{ $log->result }}</td>
                        <td class="px-4 py-3 text-xs text-stone-600">
                            {{ $log->metadata ? json_encode($log->metadata, JSON_UNESCAPED_UNICODE) : '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-stone-500">Belum ada catatan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $logs->links() }}</div>
@endsection
