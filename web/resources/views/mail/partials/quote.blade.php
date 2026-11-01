{{--
    Kutipan pesan masuk, dipakai pada email enquiry supaya isi pesan terbaca
    sebagai pesan tamu, bukan sebagai teks aplikasi. Parameter: $text.
--}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 20px;">
    <tr>
        <td style="border-left:3px solid #E0D2BE; padding:2px 0 2px 14px;">
            <p style="margin:0; font-family:Georgia, 'Times New Roman', serif; font-size:16px; line-height:1.7; color:#3B342E;">
                {!! nl2br(e($text)) !!}
            </p>
        </td>
    </tr>
</table>
