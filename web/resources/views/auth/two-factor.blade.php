@extends('layouts.guest')

@section('title', 'Verifikasi dua faktor')

@section('content')
    <h1 class="text-xl font-semibold">Verifikasi dua faktor</h1>
    <p class="mt-2 text-sm text-stone-600">
        Masukkan kode 6 digit dari aplikasi autentikator. Bila perangkat hilang, gunakan salah satu
        kode pemulihan yang disimpan saat pendaftaran.
    </p>

    <form method="POST" action="{{ route('two-factor.login') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <label for="code" class="block text-sm font-medium">Kode autentikator</label>
            <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code"
                   pattern="[0-9 ]{6,10}" autofocus
                   class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-rose-700 focus:outline-none focus:ring-1 focus:ring-rose-700">
        </div>

        <div class="border-t border-stone-200 pt-4">
            <label for="recovery_code" class="block text-sm font-medium">Kode pemulihan</label>
            <input id="recovery_code" name="recovery_code" type="text" autocomplete="off"
                   class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-rose-700 focus:outline-none focus:ring-1 focus:ring-rose-700">
            <p class="mt-1 text-xs text-stone-500">Isi salah satu kolom saja. Setiap kode pemulihan hanya berlaku sekali.</p>
        </div>

        <button type="submit" class="w-full rounded-md bg-rose-800 px-4 py-2 text-sm font-medium text-white hover:bg-rose-900">
            Verifikasi
        </button>
    </form>

    <div class="mt-6 text-sm">
        <a href="{{ route('login') }}" class="text-stone-600 hover:underline">Kembali ke halaman masuk</a>
    </div>
@endsection
