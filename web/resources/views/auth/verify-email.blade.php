@extends('layouts.guest')

@section('title', 'Verifikasi email')

@section('content')
    <h1 class="text-xl font-semibold">Verifikasi email Anda</h1>

    <p class="mt-3 text-sm text-stone-600">
        Kami mengirim tautan verifikasi ke <span class="font-medium text-stone-900">{{ auth()->user()->email }}</span>.
        Buka tautan tersebut agar undangan dan pesanan terhubung ke alamat yang benar.
    </p>

    <p class="mt-2 text-sm text-stone-600">
        Belum menerima email? Periksa folder spam, atau kirim ulang tautan di bawah ini.
    </p>

    <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
        @csrf
        <button type="submit" class="w-full rounded-md bg-rose-800 px-4 py-2 text-sm font-medium text-white hover:bg-rose-900">
            Kirim ulang tautan verifikasi
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-3">
        @csrf
        <button type="submit" class="w-full rounded-md border border-stone-300 bg-white px-4 py-2 text-sm font-medium hover:border-stone-400">
            Keluar
        </button>
    </form>

    <p class="mt-6 text-xs text-stone-500">
        Alamat email salah? Hubungi {{ config('site.contact.email') }} agar tim kami memperbaruinya.
    </p>
@endsection
