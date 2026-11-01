@extends('layouts.app')

@section('title', 'Tambah tamu')

@section('content')
    <div>
        <a href="{{ route('weddings.guests.index', $wedding) }}" class="text-sm text-stone-500 hover:underline">
            &larr; Tamu &amp; RSVP
        </a>
        <h1 class="mt-2 font-serif text-2xl font-semibold">Tambah tamu</h1>
        <p class="mt-1 text-sm text-stone-600">{{ $wedding->internal_title }}</p>
    </div>

    @include('guests.partials.form', [
        'action' => route('weddings.guests.store', $wedding),
        'method' => 'POST',
        'submitLabel' => 'Simpan tamu',
    ])
@endsection
