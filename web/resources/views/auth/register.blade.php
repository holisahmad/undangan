@extends('layouts.guest')

@section('title', 'Buat akun')

@section('content')
    <h1 class="text-xl font-semibold">Buat akun</h1>

    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium">Nama</label>
            <input id="name" name="name" type="text" required autofocus
                   value="{{ old('name') }}"
                   class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-rose-700 focus:outline-none focus:ring-1 focus:ring-rose-700">
        </div>

        <div>
            <label for="email" class="block text-sm font-medium">Email</label>
            <input id="email" name="email" type="email" required
                   value="{{ old('email') }}"
                   class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-rose-700 focus:outline-none focus:ring-1 focus:ring-rose-700">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium">Kata sandi</label>
            <input id="password" name="password" type="password" required
                   class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-rose-700 focus:outline-none focus:ring-1 focus:ring-rose-700">
            <p class="mt-1 text-xs text-stone-500">Minimal 8 karakter.</p>
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium">Ulangi kata sandi</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required
                   class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-rose-700 focus:outline-none focus:ring-1 focus:ring-rose-700">
        </div>

        <button type="submit" class="w-full rounded-md bg-rose-800 px-4 py-2 text-sm font-medium text-white hover:bg-rose-900">
            Daftar
        </button>
    </form>

    <p class="mt-6 text-sm text-stone-600">
        Sudah punya akun?
        <a href="{{ route('login') }}" class="text-rose-800 hover:underline">Masuk</a>
    </p>
@endsection
