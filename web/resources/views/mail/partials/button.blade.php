{{--
    Tombol aksi. Parameter: $label, $url, $variant (primary|secondary).
    Disusun dua tabel bersarang supaya sudut membulat tetap tampil di Gmail
    (padding ada di <a>, bukan di <td>, agar seluruh tombol bisa diklik).
--}}
@php
    $secondary = ($variant ?? 'primary') === 'secondary';
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:2px 0 18px;">
    <tr>
        <td align="left">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td align="center" bgcolor="{{ $secondary ? '#FFFFFF' : '#A6294B' }}"
                        @style(['border-radius: 8px', 'border: 1px solid #E0D2BE' => $secondary])>
                        <a href="{{ $url }}" target="_blank"
                           @style([
                               'display: inline-block',
                               'padding: 13px 26px',
                               'font-family: Helvetica, Arial, sans-serif',
                               'font-size: 14px',
                               'font-weight: bold',
                               'letter-spacing: 0.3px',
                               'text-decoration: none',
                               'border-radius: 8px',
                               'color: #A6294B' => $secondary,
                               'color: #FFFFFF' => ! $secondary,
                           ])>
                            {{ $label }}
                        </a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
