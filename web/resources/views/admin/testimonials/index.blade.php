@extends('layouts.app')

@section('title', 'Admin &middot; Testimoni')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="font-serif text-2xl font-semibold">Admin &middot; Testimoni</h1>
            <p class="mt-1 text-sm text-stone-600">
                {{ $testimonials->total() }} testimoni.
                @if ($pendingCount > 0)
                    <span class="font-medium text-rose-800">{{ $pendingCount }} menunggu peninjauan.</span>
                @endif
                Hanya testimoni disetujui yang tampil di landing page.
            </p>
        </div>
    </div>

    @include('admin.partials.nav')

    <form method="GET" action="{{ route('admin.testimonials.index') }}" class="mt-6 flex flex-wrap items-end gap-3">
        <div>
            <label for="status" class="block text-sm font-medium">Status</label>
            <select id="status" name="status"
                    class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-rose-700 focus:outline-none focus:ring-1 focus:ring-rose-700">
                <option value="">Semua status</option>
                <option value="pending" @selected($status === 'pending')>Menunggu</option>
                <option value="approved" @selected($status === 'approved')>Disetujui</option>
                <option value="rejected" @selected($status === 'rejected')>Ditolak</option>
            </select>
        </div>
        <button type="submit" class="rounded-md bg-stone-900 px-4 py-2 text-sm font-medium text-white hover:bg-stone-800">
            Filter
        </button>
        @if ($status !== '')
            <a href="{{ route('admin.testimonials.index') }}" class="text-sm text-stone-600 underline-offset-4 hover:underline">Reset</a>
        @endif
    </form>

    <div class="mt-6 space-y-3">
        @forelse ($testimonials as $testimonial)
            <article class="rounded-lg border border-stone-200 bg-white p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-sm text-amber-600" aria-label="Penilaian {{ $testimonial->rating }} dari 5">
                            @for ($star = 1; $star <= 5; $star++)
                                <span aria-hidden="true">{{ $star <= $testimonial->rating ? '★' : '☆' }}</span>
                            @endfor
                        </p>
                        <p class="mt-2 whitespace-pre-line text-sm text-stone-800">{{ $testimonial->quote }}</p>
                        <p class="mt-2 text-xs text-stone-500">
                            {{ $testimonial->user?->name ?? 'Pengguna' }}
                            &middot; {{ $testimonial->wedding?->internal_title ?? 'Undangan' }}
                            &middot; dikirim {{ $testimonial->created_at?->translatedFormat('d M Y H:i') }}
                        </p>
                    </div>
                    <div class="text-right text-xs">
                        @if ($testimonial->status === \App\Models\Testimonial::STATUS_APPROVED)
                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 font-medium text-emerald-900">Disetujui</span>
                        @elseif ($testimonial->status === \App\Models\Testimonial::STATUS_REJECTED)
                            <span class="rounded-full bg-stone-200 px-2 py-0.5 font-medium text-stone-700">Ditolak</span>
                        @else
                            <span class="rounded-full bg-amber-100 px-2 py-0.5 font-medium text-amber-900">Menunggu</span>
                        @endif
                        @if ($testimonial->moderated_at)
                            <p class="mt-1 text-stone-500">Ditinjau {{ $testimonial->moderated_at->translatedFormat('d M Y') }}</p>
                        @endif
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    @if ($testimonial->status !== \App\Models\Testimonial::STATUS_APPROVED)
                        <form method="POST" action="{{ route('admin.testimonials.approve', $testimonial) }}">
                            @csrf
                            <button type="submit"
                                    class="rounded-md bg-emerald-800 px-3 py-1.5 text-sm font-medium text-white hover:bg-emerald-900">
                                Setujui
                            </button>
                        </form>
                    @endif
                    @if ($testimonial->status !== \App\Models\Testimonial::STATUS_REJECTED)
                        <form method="POST" action="{{ route('admin.testimonials.reject', $testimonial) }}">
                            @csrf
                            <button type="submit"
                                    class="rounded-md border border-rose-300 bg-white px-3 py-1.5 text-sm font-medium text-rose-900 hover:border-rose-400">
                                Tolak
                            </button>
                        </form>
                    @endif
                </div>
            </article>
        @empty
            <p class="rounded-lg border border-stone-200 bg-white px-5 py-8 text-center text-sm text-stone-500">
                Tidak ada testimoni yang cocok.
            </p>
        @endforelse
    </div>

    <div class="mt-4">{{ $testimonials->links() }}</div>
@endsection
