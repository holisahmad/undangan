@php
    $partnerType = old('partner_type', $partner?->partner_type ?? 'bride');
@endphp

<div>
    <label class="{{ $labelClass }}">Peran</label>
    <select name="partner_type" class="{{ $inputClass }}">
        @foreach (['bride' => 'Mempelai wanita', 'groom' => 'Mempelai pria', 'other' => 'Lainnya'] as $value => $label)
            <option value="{{ $value }}" @selected($partnerType === $value)>{{ $label }}</option>
        @endforeach
    </select>
</div>

<div>
    <label class="{{ $labelClass }}">Nama lengkap</label>
    <input name="display_name" type="text" required maxlength="120"
           value="{{ old('display_name', $partner?->display_name) }}" class="{{ $inputClass }}">
</div>

<div>
    <label class="{{ $labelClass }}">Nama panggilan</label>
    <input name="nickname" type="text" maxlength="60"
           value="{{ old('nickname', $partner?->nickname) }}" class="{{ $inputClass }}">
</div>

<div>
    <label class="{{ $labelClass }}">Orang tua</label>
    <input name="parents" type="text" maxlength="200"
           value="{{ old('parents', $partner?->parents) }}" class="{{ $inputClass }}">
</div>

<div>
    <label class="{{ $labelClass }}">Instagram (opsional)</label>
    <input name="instagram" type="text" maxlength="60" placeholder="tanpa @"
           value="{{ old('instagram', $partner?->instagram) }}" class="{{ $inputClass }}">
</div>

<div>
    <label class="{{ $labelClass }}">Urutan tampil</label>
    <input name="display_order" type="number" min="0" max="99"
           value="{{ old('display_order', $partner?->display_order ?? 0) }}" class="{{ $inputClass }}">
</div>

<div class="sm:col-span-2">
    <label class="{{ $labelClass }}">Biografi singkat (opsional)</label>
    <textarea name="biography" rows="3" maxlength="1000" class="{{ $inputClass }}">{{ old('biography', $partner?->biography) }}</textarea>
</div>
