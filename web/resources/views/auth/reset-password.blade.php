@extends('layouts.guest')

@section('title', 'Atur ulang kata sandi')

@section('content')
    <h1 class="text-xl font-semibold">Atur ulang kata sandi</h1>

    <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label for="email" class="block text-sm font-medium">Email</label>
            <input id="email" name="email" type="email" required
                   value="{{ old('email', $email) }}"
                   class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-rose-700 focus:outline-none focus:ring-1 focus:ring-rose-700">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium">Kata sandi baru</label>
            <input id="password" name="password" type="password" required
                   class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-rose-700 focus:outline-none focus:ring-1 focus:ring-rose-700">
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium">Ulangi kata sandi baru</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required
                   class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-rose-700 focus:outline-none focus:ring-1 focus:ring-rose-700">
        </div>

        <button type="submit" class="w-full rounded-md bg-rose-800 px-4 py-2 text-sm font-medium text-white hover:bg-rose-900">
            Simpan kata sandi
        </button>
    </form>
@endsection
