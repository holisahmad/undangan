{{--
    Tabel label/nilai. Parameter: $rows, daftar baris berbentuk
    ['label' => 'Nomor pesanan', 'value' => 'INV-…', 'href' => 'https://…'].
    'href' opsional; nilainya menjadi tautan bila diisi.
--}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 20px; border-top:1px solid #EFE6DA;">
    @foreach ($rows as $row)
        @include('mail.partials.detail', [
            'label' => $row['label'],
            'value' => $row['value'],
            'href' => $row['href'] ?? null,
            'last' => $loop->last,
        ])
    @endforeach
</table>
