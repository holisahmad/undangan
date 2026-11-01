{{--
    Paragraf isi email. Parameter: $text (baris baru dihormati), $muted
    (opsional, untuk keterangan tambahan yang lebih ringan), $wrap (opsional,
    memaksa URL panjang patah agar tidak melebar di layar ponsel).
--}}
<p @style([
    'margin: 0 0 16px',
    'font-family: Helvetica, Arial, sans-serif',
    'font-size: 15px',
    'line-height: 1.7',
    'color: #6B6259' => $muted ?? false,
    'color: #3B342E' => ! ($muted ?? false),
    'word-break: break-all' => $wrap ?? false,
])>
    {!! nl2br(e($text)) !!}
</p>
