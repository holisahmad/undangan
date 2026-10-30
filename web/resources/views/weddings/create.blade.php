@extends('layouts.app')

@section('title', 'Buat undangan')

@section('content')
    <div class="max-w-3xl">
        <h1 class="font-serif text-2xl font-semibold">Buat undangan</h1>
        <p class="mt-1 text-sm text-stone-600">
            Beri nama internal untuk memudahkan pencarian. Nama ini tidak tampil di halaman publik.
        </p>

        @if ($templates->isEmpty())
            <div class="mt-8 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                Belum ada template aktif. Jalankan
                <code class="rounded bg-amber-100 px-1">php artisan db:seed --class=TemplateSeeder</code>
                terlebih dahulu.
            </div>
        @else
            <form method="POST" action="{{ route('weddings.store') }}" class="mt-8 space-y-6">
                @csrf

                <div>
                    <label for="internal_title" class="block text-sm font-medium">Nama internal</label>
                    <input id="internal_title" name="internal_title" type="text" required maxlength="160"
                           value="{{ old('internal_title') }}"
                           placeholder="Contoh: Ayu & Bima — Desember"
                           class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-rose-700 focus:outline-none focus:ring-1 focus:ring-rose-700">
                </div>

                <fieldset>
                    <legend class="text-sm font-medium">Template</legend>
                    <div class="mt-2 grid gap-3 sm:grid-cols-2">
                        @foreach ($templates as $version)
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-stone-300 bg-white p-4 hover:border-rose-700">
                                <input type="radio" name="template_version_id" value="{{ $version->getKey() }}"
                                       @checked(old('template_version_id') === $version->getKey())
                                       class="mt-1 border-stone-300 text-rose-800 focus:ring-rose-700">
                                <span>
                                    <span class="block font-medium">{{ $version->template->name }}</span>
                                    <span class="mt-1 block text-sm text-stone-600">
                                        {{ $version->template->description ?? 'Template klasik.' }}
                                    </span>
                                    <span class="mt-1 block text-xs text-stone-400">
                                        Versi {{ $version->version }} &middot; {{ $version->renderer_key }}
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div class="flex items-center gap-3">
                    <button type="submit" class="rounded-md bg-rose-800 px-4 py-2 text-sm font-medium text-white hover:bg-rose-900">
                        Buat draft
                    </button>
                    <a href="{{ route('weddings.index') }}" class="text-sm text-stone-600 hover:underline">Batal</a>
                </div>
            </form>
        @endif
    </div>
@endsection
