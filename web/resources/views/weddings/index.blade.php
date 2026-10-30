@extends('layouts.app')

@section('title', 'Undangan')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-serif text-2xl font-semibold">Undangan</h1>
            <p class="mt-1 text-sm text-stone-600">Semua undangan milik akun Anda.</p>
        </div>
        <a href="{{ route('weddings.create') }}"
           class="rounded-md bg-rose-800 px-4 py-2 text-sm font-medium text-white hover:bg-rose-900">
            Buat undangan
        </a>
    </div>

    @if ($weddings->isEmpty())
        <div class="mt-8 rounded-lg border border-dashed border-stone-300 bg-white p-8 text-center">
            <p class="font-medium">Belum ada undangan.</p>
            <a href="{{ route('weddings.create') }}" class="mt-2 inline-block text-sm text-rose-800 hover:underline">
                Buat undangan pertama
            </a>
        </div>
    @else
        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($weddings as $wedding)
                @include('weddings.partials.card', ['wedding' => $wedding])
            @endforeach
        </div>

        <div class="mt-6">
            {{ $weddings->links() }}
        </div>
    @endif
@endsection
