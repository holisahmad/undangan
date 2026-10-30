@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
    <h1 class="text-xl font-semibold">Masuk</h1>

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium">Email</label>
            <input id="email" name="email" type="email" required autofocus
                   value="{{ old('email') }}"
                   class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-rose-700 focus:outline-none focus:ring-1 focus:ring-rose-700">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium">Kata sandi</label>
            <input id="password" name="password" type="password" required
                   class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-rose-700 focus:outline-none focus:ring-1 focus:ring-rose-700">
        </div>

        <label class="flex items-center gap-2 text-sm text-stone-600">
            <input type="checkbox" name="remember" value="1" class="rounded border-stone-300">
            Ingat saya
        </label>

        <button type="submit" class="w-full rounded-md bg-rose-800 px-4 py-2 text-sm font-medium text-white hover:bg-rose-900">
            Masuk
        </button>
    </form>

    <div class="mt-6 flex items-center justify-between text-sm">
        <a href="{{ route('password.request') }}" class="text-rose-800 hover:underline">Lupa kata sandi?</a>
        <a href="{{ route('register') }}" class="text-stone-600 hover:underline">Buat akun</a>
    </div>
@endsection
