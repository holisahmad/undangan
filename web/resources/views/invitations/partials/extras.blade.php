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
    <p class="inv-gallery">
        <a href="{{ $galleryUrl ?? route('invitations.gallery', ['slug' => $invitation->slug]) }}">Lihat galeri foto &rarr;</a>
    </p>
@endif

@isset($guest)
    <style>
        .inv-rsvp {
            margin-top: 40px;
            padding: 22px 20px;
            border: 1px solid #e7e5e4;
            border-radius: 10px;
            background: #fff;
            color: #1c1917;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            text-align: left;
        }
        .inv-rsvp h2 {
            margin: 0 0 6px;
            font-family: inherit;
            font-size: 1.15rem;
            font-weight: 600;
            letter-spacing: 0;
        }
        .inv-rsvp .inv-meta { margin: 0 0 14px; font-size: .85rem; color: #57534e; }
        .inv-option { display: flex; align-items: center; gap: 8px; padding: 7px 0; font-size: .95rem; }
        .inv-field { margin-top: 12px; }
        .inv-field label { display: block; margin-bottom: 4px; font-size: .85rem; font-weight: 600; }
        .inv-field select,
        .inv-field textarea {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #d6d3d1;
            border-radius: 6px;
            background: #fff;
            color: inherit;
            font: inherit;
        }
        .inv-field textarea { min-height: 84px; resize: vertical; }
        .inv-consent {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-top: 12px;
            font-size: .82rem;
            color: #57534e;
        }
        .inv-consent input { margin-top: 2px; }
        .inv-rsvp button {
            width: 100%;
            margin-top: 14px;
            padding: 10px 16px;
            border: 0;
            border-radius: 6px;
            background: #9f1239;
            color: #fff;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
        }
        .inv-rsvp button:hover { background: #881337; }
        .inv-status {
            margin: 0 0 12px;
            padding: 10px 12px;
            border: 1px solid #a7f3d0;
            border-radius: 6px;
            background: #ecfdf5;
            color: #064e3b;
            font-size: .85rem;
        }
        .inv-errors {
            margin: 0 0 12px;
            padding: 10px 12px;
            border: 1px solid #fecdd3;
            border-radius: 6px;
            background: #fff1f2;
            color: #881337;
            font-size: .85rem;
        }
        .inv-errors ul { margin: 6px 0 0; padding-left: 20px; }
        .inv-gallery { margin: 28px 0 0; text-align: center; }
        .inv-gallery a {
            display: inline-block;
            padding: 9px 20px;
            border: 1px solid currentColor;
            border-radius: 999px;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            font-size: .85rem;
            text-decoration: none;
        }
    </style>
@endisset
