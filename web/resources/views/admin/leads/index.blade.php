@extends('layouts.app')

@section('title', 'Admin &middot; Lead')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="font-serif text-2xl font-semibold">Admin &middot; Lead</h1>
            <p class="mt-1 text-sm text-stone-600">
                {{ $leads->total() }} permintaan dari formulir kontak.
                @if ($newCount > 0)
                    <span class="font-medium text-rose-800">{{ $newCount }} belum dihubungi.</span>
                @endif
            </p>
        </div>
    </div>

    @include('admin.partials.nav')

    <form method="GET" action="{{ route('admin.leads.index') }}" class="mt-6 flex flex-wrap items-end gap-3">
        <div class="w-full flex-1 sm:min-w-[18rem]">
            <label for="q" class="block text-sm font-medium">Cari nama, email, atau WhatsApp</label>
            <input id="q" name="q" type="search" value="{{ $search }}"
                   class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-rose-700 focus:outline-none focus:ring-1 focus:ring-rose-700">
        </div>
        <div>
            <label for="status" class="block text-sm font-medium">Status</label>
            <select id="status" name="status"
                    class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-rose-700 focus:outline-none focus:ring-1 focus:ring-rose-700">
                <option value="">Semua status</option>
                <option value="new" @selected($status === 'new')>Belum dihubungi</option>
                <option value="contacted" @selected($status === 'contacted')>Sudah dihubungi</option>
            </select>
        </div>
        <button type="submit" class="rounded-md bg-stone-900 px-4 py-2 text-sm font-medium text-white hover:bg-stone-800">
            Filter
        </button>
        @if ($search !== '' || $status !== '')
            <a href="{{ route('admin.leads.index') }}" class="text-sm text-stone-600 underline-offset-4 hover:underline">Reset</a>
        @endif
    </form>

    <div class="mt-6 space-y-3">
        @forelse ($leads as $lead)
            <article class="rounded-lg border border-stone-200 bg-white p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-medium">{{ $lead->name }}</p>
                        <p class="text-xs text-stone-500">
                            {{ $lead->email }}
                            @if ($lead->whatsapp)
                                &middot; {{ $lead->whatsapp }}
                            @endif
                        </p>
                    </div>
                    <div class="text-right text-xs text-stone-500">
                        @if ($lead->isNew())
                            <span class="rounded-full bg-rose-100 px-2 py-0.5 font-medium text-rose-900">Baru</span>
                        @else
                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 font-medium text-emerald-900">Sudah dihubungi</span>
                        @endif
                        <p class="mt-1">{{ $lead->created_at?->translatedFormat('d M Y H:i') }}</p>
                    </div>
                </div>

                @if ($lead->message)
                    <p class="mt-3 whitespace-pre-line text-sm text-stone-700">{{ $lead->message }}</p>
                @endif

                <dl class="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-xs text-stone-500">
                    <div>
                        <dt class="inline">Sumber:</dt>
                        <dd class="inline">{{ $lead->source ?? '—' }}</dd>
                    </div>
                    {{-- A service enquiry has to be recognisable without opening the
                         message, because it is answered by a partner conversation. --}}
                    @if ($lead->serviceInterestLabel() !== null)
                        <div>
                            <dt class="inline">Layanan:</dt>
                            <dd class="inline font-medium text-stone-700">{{ $lead->serviceInterestLabel() }}</dd>
                        </div>
                    @endif
                    @if ($lead->utm_source || $lead->utm_medium || $lead->utm_campaign)
                        <div>
                            <dt class="inline">Kampanye:</dt>
                            <dd class="inline">
                                {{ collect([$lead->utm_source, $lead->utm_medium, $lead->utm_campaign])->filter()->implode(' / ') }}
                            </dd>
                        </div>
                    @endif
                </dl>

                @if ($lead->isNew())
                    <form method="POST" action="{{ route('admin.leads.contacted', $lead) }}" class="mt-3">
                        @csrf
                        <button type="submit"
                                class="rounded-md border border-stone-300 bg-white px-3 py-1.5 text-sm font-medium hover:border-stone-400">
                            Tandai sudah dihubungi
                        </button>
                    </form>
                @endif
            </article>
        @empty
            <p class="rounded-lg border border-stone-200 bg-white px-5 py-8 text-center text-sm text-stone-500">
                Belum ada permintaan yang cocok.
            </p>
        @endforelse
    </div>

    <div class="mt-4">{{ $leads->links() }}</div>
@endsection
