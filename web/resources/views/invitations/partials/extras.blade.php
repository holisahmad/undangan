@php
    $rsvp = $rsvp ?? null;
    $guest = $guest ?? null;
    $currentStatus = old('status', $rsvp?->status?->value ?? 'attending');
    $currentPartySize = (int) old('party_size', $rsvp?->party_size ?? 1);
    $galleryAvailable = $hasGallery ?? false;
    // New responses default to public (the wall is the point of the field), but
    // an existing private message keeps its stored choice.
    $showMessage = (string) old('show_message', $rsvp?->show_message ?? true) === '1';
@endphp

@isset($guest)
    <section class="inv-rsvp" aria-label="Konfirmasi kehadiran" id="rsvp">
        <h2>Konfirmasi kehadiran</h2>
        <p class="inv-meta">
            @if ($rsvp === null)
                Silakan beri tahu kami apakah Anda dapat hadir.
            @else
                Respons terakhir Anda: <strong>{{ $rsvp->status->label() }}</strong>
                @if ($rsvp->status === \App\Enums\RsvpStatus::Attending)
                    ({{ $rsvp->party_size }} orang)
                @endif
                pada {{ $rsvp->submitted_at->locale(app()->getLocale())->translatedFormat('d F Y · H:i') }}.
                Anda dapat mengubahnya kapan saja.
            @endif
        </p>

        @if (session('rsvp_saved'))
            <div class="inv-status" role="status">
                Terima kasih. RSVP Anda telah kami terima dan dapat diubah kembali jika ada perubahan rencana.
            </div>
        @endif

        @if ($errors->any())
            <div class="inv-errors" role="alert">
                <strong>Periksa kembali:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('guests.invitation.rsvp', ['token' => $token]) }}">
            @csrf

            <label class="inv-option">
                <input type="radio" name="status" value="attending" @checked($currentStatus === 'attending')>
                <span>Hadir</span>
            </label>
            <label class="inv-option">
                <input type="radio" name="status" value="maybe" @checked($currentStatus === 'maybe')>
                <span>Masih ragu</span>
            </label>
            <label class="inv-option">
                <input type="radio" name="status" value="declined" @checked($currentStatus === 'declined')>
                <span>Tidak hadir</span>
            </label>

            <div class="inv-field">
                <label for="party_size">Jumlah peserta (maksimal {{ $guest->allowed_party_size }} orang)</label>
                <select id="party_size" name="party_size">
                    @for ($size = 1; $size <= $guest->allowed_party_size; $size++)
                        <option value="{{ $size }}" @selected($currentPartySize === $size)>{{ $size }} orang</option>
                    @endfor
                </select>
            </div>

            <div class="inv-field">
                <label for="message">Ucapan atau catatan (opsional)</label>
                <textarea id="message" name="message" maxlength="500"
                          placeholder="Contoh: Kami berdua akan hadir.">{{ old('message', $rsvp?->message) }}</textarea>
            </div>

            <label class="inv-consent">
                <input type="hidden" name="show_message" value="0">
                <input type="checkbox" name="show_message" value="1" @checked($showMessage)>
                <span>Tampilkan ucapan saya di halaman undangan</span>
            </label>

            <button type="submit">Kirim RSVP</button>
        </form>
    </section>
@endisset

@if ($galleryAvailable)
    <p class="inv-gallery-link">
        <a href="{{ $galleryUrl ?? route('invitations.gallery', ['slug' => $invitation->slug]) }}">Lihat galeri foto</a>
    </p>
@endif
