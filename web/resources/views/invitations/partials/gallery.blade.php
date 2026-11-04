{{--
    Shared photo strip. Rendered only when the invitation really has photos, so
    a couple who has not published an album never gets an empty grid, and a demo
    page never borrows a stranger's photographs for a real invitation.
--}}
@if (($galleryImages ?? []) !== [])
    <section class="inv-gallery reveal-on-scroll" aria-label="Galeri foto">
        <p class="inv-gallery__eyebrow">Dokumentasi</p>
        <h2 class="inv-gallery__title">Momen Bahagia</h2>

        <div class="gallery-grid">
            @foreach ($galleryImages as $index => $image)
                <figure class="gallery-item">
                    <img src="{{ $image }}" alt="Foto undangan {{ $index + 1 }}"
                         width="600" height="600" loading="lazy" decoding="async">
                </figure>
            @endforeach
        </div>
    </section>
@endif
