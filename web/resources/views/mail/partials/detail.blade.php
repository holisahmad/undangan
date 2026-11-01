{{--
    Satu baris label/nilai pada email, mengikuti pola email contoh: label kecil
    huruf kapital dengan jarak lebar, nilai di bawahnya, garis pemisah halus.

    Parameter: $label, $value, $href (opsional, membuat nilai bisa diklik),
    $last (opsional, untuk menghilangkan garis bawah pada baris terakhir).
--}}
<tr>
    <td @style(['padding: 12px 0', 'border-bottom: 1px solid #EFE6DA' => ! ($last ?? false)])>
        <p style="margin:0; font-family:Helvetica, Arial, sans-serif; font-size:10px; font-weight:bold; letter-spacing:2px; text-transform:uppercase; color:#A79C8E;">
            {{ $label }}
        </p>
        <p style="margin:5px 0 0; font-family:Helvetica, Arial, sans-serif; font-size:15px; line-height:1.6; color:#1C1917;">
            @if (! empty($href))
                <a href="{{ $href }}" target="_blank" style="color:#A6294B; text-decoration:none;">{!! nl2br(e($value)) !!}</a>
            @else
                {!! nl2br(e($value)) !!}
            @endif
        </p>
    </td>
</tr>
