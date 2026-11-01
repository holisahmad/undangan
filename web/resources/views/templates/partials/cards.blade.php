{{--
    The result grid and its control. Rendered whole on the page and rendered
    alone when the visitor asks for the next page, so both paths show the same
    cards instead of a JavaScript-only imitation of them.
--}}
@if ($templates->isEmpty())
    <div class="rounded-lg border border-dashed border-stone-300 bg-white p-8 text-sm text-stone-600">
        @if ($category === null && $tier === null)
            {{-- Nothing was filtered away, so the catalogue itself holds no
                 published theme yet. Blaming the visitor's choices and offering
                 a link back to this very page would be a dead end. --}}
            <p>Belum ada tema yang dapat ditampilkan saat ini.</p>
            <p class="mt-2">
                Sambil menunggu desain baru masuk, lihat
                <a class="font-medium text-rose-800 underline-offset-4 hover:underline" href="{{ route('home') }}#contoh">
                    contoh undangan
                </a>
                atau
                <a class="font-medium text-rose-800 underline-offset-4 hover:underline" href="{{ route('home') }}#harga">
                    paket dan harga yang tersedia
                </a>, lalu
                <a class="font-medium text-rose-800 underline-offset-4 hover:underline" href="{{ route('home') }}#kontak">
                    konsultasikan tema
                </a>
                yang Anda bayangkan.
            </p>
        @else
            <p>Belum ada tema pada kombinasi pilihan ini.</p>
            <p class="mt-2">
                <a class="font-medium text-rose-800 underline-offset-4 hover:underline" href="{{ route('templates.index') }}">
                    Lihat semua tema
                </a>
                atau pilih paket/kategori lain.
            </p>
        @endif
    </div>
@else
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" data-catalog-grid>
        @foreach ($templates as $template)
            @include('invitations.partials.template-card', [
                'template' => $template,
                'version' => $template->versions->first(),
            ])
        @endforeach
    </div>

    <div class="mt-8 flex flex-col items-center gap-3 text-center" data-catalog-pager>
        @if ($nextUrl !== null)
            {{-- Without JavaScript this is a normal link to the next page. With it,
                 the same URL is fetched as this fragment and appended below. --}}
            <a href="{{ $nextUrl }}" data-load-more data-load-more-url="{{ $fragmentUrl }}"
               data-load-more-busy="Memuat…" data-load-more-done="Semua tema sudah ditampilkan"
               class="rounded-md border border-stone-300 bg-white px-5 py-3 text-sm font-medium text-stone-800 hover:border-rose-300 hover:text-rose-800">
                Tampilkan lebih banyak tema
            </a>
        @endif

        {{-- The count is the one number the enhancement has to keep truthful, so
             it is a value the script adds to rather than a sentence it rewrites. --}}
        <p class="text-xs text-stone-500" role="status" aria-live="polite">
            Menampilkan <span data-catalog-shown>{{ count($templates->items()) }}</span>
            dari {{ $templates->total() }} tema
            @if ($category !== null || $tier !== null)
                pada pilihan ini
            @endif
        </p>
    </div>
@endif
