@extends('layouts.guest')

@section('title', 'Lupa kata sandi')

@section('content')
    <h1 class="text-xl font-semibold">Lupa kata sandi</h1>
    <p class="mt-2 text-sm text-stone-600">
        Masukkan email akun Anda. Kami akan mengirim tautan untuk membuat kata sandi baru.
    </p>

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium">Email</label>
            <input id="email" name="email" type="email" required autofocus
                   value="{{ old('email') }}"
                   class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-rose-700 focus:outline-none focus:ring-1 focus:ring-rose-700">
        </div>

        <button type="submit" class="w-full rounded-md bg-rose-800 px-4 py-2 text-sm font-medium text-white hover:bg-rose-900">
            Kirim tautan
        </button>
    </form>

    <p class="mt-6 text-sm">
        <a href="{{ route('login') }}" class="text-rose-800 hover:underline">Kembali ke halaman masuk</a>
    </p>
@endsection
