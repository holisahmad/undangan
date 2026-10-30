@php
    $eventType = old('event_type', $event?->event_type ?? 'resepsi');
    $startsAt = old(
        'starts_at',
        $event?->starts_at?->copy()->setTimezone($event->timezone)->format('Y-m-d\TH:i'),
    );
    $endsAt = old(
        'ends_at',
        $event?->ends_at?->copy()->setTimezone($event->timezone)->format('Y-m-d\TH:i'),
    );
@endphp

<div>
    <label class="{{ $labelClass }}">Jenis acara</label>
    <select name="event_type" class="{{ $inputClass }}">
        @foreach (['akad' => 'Akad', 'resepsi' => 'Resepsi', 'other' => 'Lainnya'] as $value => $label)
            <option value="{{ $value }}" @selected($eventType === $value)>{{ $label }}</option>
        @endforeach
    </select>
</div>

<div>
    <label class="{{ $labelClass }}">Nama acara</label>
    <input name="title" type="text" required maxlength="120"
           value="{{ old('title', $event?->title) }}" class="{{ $inputClass }}">
</div>

<div>
    <label class="{{ $labelClass }}">Mulai</label>
    <input name="starts_at" type="datetime-local" required value="{{ $startsAt }}" class="{{ $inputClass }}">
</div>

<div>
    <label class="{{ $labelClass }}">Selesai (opsional)</label>
    <input name="ends_at" type="datetime-local" value="{{ $endsAt }}" class="{{ $inputClass }}">
</div>

<div>
    <label class="{{ $labelClass }}">Zona waktu</label>
    <input name="timezone" type="text" maxlength="64"
           value="{{ old('timezone', $event?->timezone ?? 'Asia/Jakarta') }}" class="{{ $inputClass }}">
    <p class="mt-1 text-xs text-stone-500">Contoh: Asia/Jakarta, Asia/Makassar, Asia/Jayapura.</p>
</div>

<div>
    <label class="{{ $labelClass }}">Nama tempat</label>
    <input name="venue_name" type="text" required maxlength="160"
           value="{{ old('venue_name', $event?->venue_name) }}" class="{{ $inputClass }}">
</div>

<div class="sm:col-span-2">
    <label class="{{ $labelClass }}">Alamat</label>
    <textarea name="address" rows="2" maxlength="500" class="{{ $inputClass }}">{{ old('address', $event?->address) }}</textarea>
</div>

<div class="sm:col-span-2">
    <label class="{{ $labelClass }}">Tautan peta (opsional)</label>
    <input name="map_url" type="url" maxlength="2048" placeholder="https://maps.google.com/..."
           value="{{ old('map_url', $event?->map_url) }}" class="{{ $inputClass }}">
</div>

<div>
    <label class="{{ $labelClass }}">Urutan tampil</label>
    <input name="display_order" type="number" min="0" max="99"
           value="{{ old('display_order', $event?->display_order ?? 0) }}" class="{{ $inputClass }}">
</div>
