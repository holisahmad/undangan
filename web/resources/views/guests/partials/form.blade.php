@php
    $groupOptions = $groups ?? collect();
    $maxPartySize = (int) config('platform.guests.max_party_size', 20);
@endphp

<form method="POST" action="{{ $action }}" class="mt-6 max-w-2xl space-y-5">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <label for="display_name" class="block text-sm font-medium">Nama tamu</label>
        <input id="display_name" name="display_name" type="text" required maxlength="120"
               value="{{ old('display_name', $guest->display_name) }}"
               placeholder="Contoh: Bapak Ahmad dan keluarga"
               class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-rose-700 focus:outline-none focus:ring-1 focus:ring-rose-700">
        <p class="mt-1 text-xs text-stone-500">Nama ini tampil sebagai sapaan pada tautan personal tamu.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="guest_group_id" class="block text-sm font-medium">Grup</label>
            <select id="guest_group_id" name="guest_group_id"
                    class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm">
                <option value="">Tanpa grup</option>
                @foreach ($groupOptions as $group)
                    <option value="{{ $group->getKey() }}"
                        @selected((string) old('guest_group_id', $guest->guest_group_id) === (string) $group->getKey())>
                        {{ $group->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="allowed_party_size" class="block text-sm font-medium">Kuota peserta</label>
            <input id="allowed_party_size" name="allowed_party_size" type="number" required min="1"
                   max="{{ $maxPartySize }}"
                   value="{{ old('allowed_party_size', $guest->allowed_party_size ?? 1) }}"
                   class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm">
            <p class="mt-1 text-xs text-stone-500">Jumlah maksimum orang pada satu RSVP (1&ndash;{{ $maxPartySize }}).</p>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="contact_phone" class="block text-sm font-medium">Telepon (opsional)</label>
            <input id="contact_phone" name="contact_phone" type="text" maxlength="30"
                   value="{{ old('contact_phone', $guest->contact_phone) }}"
                   class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm">
        </div>

        <div>
            <label for="contact_email" class="block text-sm font-medium">Email (opsional)</label>
            <input id="contact_email" name="contact_email" type="email" maxlength="190"
                   value="{{ old('contact_email', $guest->contact_email) }}"
                   class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm">
        </div>
    </div>

    <p class="rounded-md border border-stone-200 bg-stone-50 px-3 py-2 text-xs text-stone-600">
        Telepon dan email hanya terlihat oleh Anda dan tidak pernah tampil di halaman undangan maupun tautan tamu.
    </p>

    <div class="flex items-center gap-3">
        <button type="submit" class="rounded-md bg-rose-800 px-4 py-2 text-sm font-medium text-white hover:bg-rose-900">
            {{ $submitLabel }}
        </button>
        <a href="{{ route('weddings.guests.index', $wedding) }}" class="text-sm text-stone-600 hover:underline">Batal</a>
    </div>
</form>
