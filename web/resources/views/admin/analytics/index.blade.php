@extends('layouts.app')

@section('title', 'Admin &middot; Analitik')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="font-serif text-2xl font-semibold">Admin &middot; Analitik</h1>
            <p class="mt-1 text-sm text-stone-600">
                Funnel {{ $windowDays }} hari terakhir, dihitung dari event agregat. Tidak ada data per orang,
                alamat IP, atau user agent yang disimpan.
            </p>
        </div>
    </div>

    @include('admin.partials.nav')

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($rows as $row)
            @php
                $delta = $row['previous'] === 0 ? null : (int) round(($row['current'] - $row['previous']) / $row['previous'] * 100);
            @endphp
            <section class="rounded-lg border border-stone-200 bg-white p-5">
                <h2 class="text-sm font-medium text-stone-600">{{ $row['label'] }}</h2>
                <p class="mt-2 text-2xl font-semibold">{{ number_format($row['current'], 0, ',', '.') }}</p>
                <p class="mt-1 text-xs text-stone-500">
                    {{ number_format($row['previous'], 0, ',', '.') }} pada {{ $windowDays }} hari sebelumnya
                    @if ($delta !== null)
                        <span class="{{ $delta >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                            ({{ $delta >= 0 ? '+' : '' }}{{ $delta }}%)
                        </span>
                    @endif
                </p>
            </section>
        @endforeach
    </div>

    <section class="mt-8 rounded-lg border border-stone-200 bg-white p-5">
        <h2 class="font-serif text-lg font-semibold">Konversi antar langkah</h2>
        <p class="mt-1 text-sm text-stone-600">
            Rasio dihitung terhadap langkah sebelumnya pada periode yang sama, sehingga angka kecil tidak
            menyamarkan langkah yang bocor.
        </p>

        @if ($totalEvents === 0)
            <p class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                Belum ada event tercatat. Funnel terisi otomatis saat pengunjung membuka landing page,
                mengirim formulir, mendaftar, menerbitkan undangan, dan mengirim RSVP.
            </p>
        @else
            <p class="mt-2 text-xs text-stone-500">Sejak {{ $since->translatedFormat('d F Y H:i') }}</p>

            <ol class="mt-4 space-y-3">
                @foreach ($funnel as $step)
                    <li>
                        <div class="flex flex-wrap items-baseline justify-between gap-2 text-sm">
                            <span class="font-medium">{{ $step['label'] }}</span>
                            <span class="text-stone-600">
                                {{ number_format($step['count'], 0, ',', '.') }} event
                                @if ($step['rate'] !== null)
                                    &middot; {{ $step['rate'] }}% dari langkah sebelumnya
                                @endif
                            </span>
                        </div>
                        @php
                            $width = $funnel[0]['count'] > 0 ? min(100, (int) round($step['count'] / $funnel[0]['count'] * 100)) : 0;
                        @endphp
                        <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-stone-100"
                             role="img" aria-label="{{ $step['label'] }}: {{ $step['count'] }} event">
                            <div class="h-2 rounded-full bg-rose-800" @style(['width: '.$width.'%'])></div>
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif
    </section>
@endsection
