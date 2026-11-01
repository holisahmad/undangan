@extends('layouts.app')

@section('title', 'Pilih paket')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="{{ route('weddings.show', $wedding) }}" class="text-sm text-stone-500 hover:underline">&larr; {{ $wedding->internal_title }}</a>
            <h1 class="mt-2 font-serif text-2xl font-semibold">Pilih paket undangan</h1>
            <p class="mt-1 text-sm text-stone-600">
                Harga dihitung di server dan dibekukan saat pesanan dibuat. Masa aktif baru dimulai setelah
                pembayaran terverifikasi.
            </p>
        </div>
    </div>

    @if ($entitlement)
        <p class="mt-6 rounded-md border border-stone-200 bg-white px-4 py-3 text-sm text-stone-700">
            Undangan ini aktif sampai
            <span class="font-medium">{{ $entitlement->expires_at?->locale(app()->getLocale())->translatedFormat('d F Y') ?? 'tanpa batas' }}</span>.
            Pembelian baru akan memperpanjang masa aktif setelah periode berjalan berakhir.
        </p>
    @endif

    @if ($plans->isEmpty())
        <div class="mt-8 rounded-lg border border-dashed border-stone-300 bg-white p-8 text-center text-sm text-stone-600">
            Belum ada paket yang dijual saat ini. Hubungi admin untuk aktivasi manual.
        </div>
    @else
        <div class="mt-8 grid gap-4 md:grid-cols-3">
            @foreach ($plans as $plan)
                <section class="flex flex-col rounded-lg border border-stone-200 bg-white p-5">
                    <h2 class="font-serif text-lg font-semibold">{{ $plan->name }}</h2>
                    <p class="mt-1 text-2xl font-semibold">Rp{{ number_format($plan->price_amount, 0, ',', '.') }}</p>
                    <p class="mt-1 text-xs text-stone-500">Masa aktif {{ $plan->duration_months }} bulan</p>

                    <ul class="mt-4 flex-1 space-y-1 text-sm text-stone-700">
                        @foreach ($plan->features as $feature)
                            <li>
                                <span class="text-stone-500">{{ \App\Support\Billing\PlanFeatureLabel::name($feature->feature_key) }}:</span>
                                <span class="font-medium">{{ \App\Support\Billing\PlanFeatureLabel::value($feature) }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <form method="POST" action="{{ route('weddings.orders.store', $wedding) }}" class="mt-5">
                        @csrf
                        <input type="hidden" name="plan_id" value="{{ $plan->getKey() }}">
                        <button type="submit"
                                class="w-full rounded-md bg-rose-800 px-4 py-2 text-sm font-medium text-white hover:bg-rose-900">
                            Buat pesanan
                        </button>
                    </form>
                </section>
            @endforeach
        </div>

        <p class="mt-6 text-xs text-stone-500">
            Harga paket adalah hipotesis produk dan dapat berubah sebelum pembayaran. Nilai yang berlaku
            adalah nilai yang tersimpan pada pesanan Anda.
        </p>
    @endif
@endsection
