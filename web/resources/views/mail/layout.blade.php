{{--
    Kerangka email transaksional bermerek. Disusun dengan tabel (bukan flexbox)
    supaya Outlook dan Gmail mobile render sama; semua gaya ditulis inline lewat
    direktif @style karena sebagian klien membuang blok CSS di bagian head.

    Dipakai lewat: ->view(['html' => 'mail.notifications.xxx', 'text' => ...], [...])
    Setiap notifikasi mengisi @section('title'), 'preheader', 'eyebrow', 'heading',
    dan 'content'. Logo digambar sebagai monogram huruf, bukan gambar remote,
    karena klien email memblokir gambar remote secara default dan emblem yang
    gagal dimuat akan tampil sebagai kotak rusak di kepala email.
--}}
@php
    $siteName = (string) config('app.name');
    $operator = (string) config('site.operator');
    $contact = config('site.contact');
    $addressLines = App\Support\Contact\BusinessAddress::lines((array) config('site.address', []));
    $supportEmail = (string) ($contact['email'] ?? '');
    $supportPhone = App\Support\Contact\PhoneNumber::display((string) ($contact['phone'] ?? ''));
    $homeUrl = route('home');
@endphp
<!DOCTYPE html>
<html lang="id" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light only">
    <title>@yield('title', $siteName)</title>
    <!--[if mso]>
    <style>body, table, td, p, a { font-family: Arial, Helvetica, sans-serif !important; }</style>
    <![endif]-->
</head>
<body style="margin:0; padding:0; background-color:#F7F1E8; -webkit-text-size-adjust:100%;">

{{-- Cuplikan yang muncul di daftar inbox sebelum email dibuka. --}}
<div style="display:none; font-size:1px; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden; color:#F7F1E8;">
    @yield('preheader')
</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F7F1E8;">
    <tr>
        <td align="center" style="padding:28px 12px;">

            <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"
                   style="width:100%; max-width:600px; background-color:#FFFDF9; border:1px solid #EADFCF; border-radius:16px; overflow:hidden;">

                {{-- Kepala: monogram di atas burgundy, nama produk, nama usaha. --}}
                <tr>
                    <td style="background-color:#A6294B; padding:24px 30px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td width="48" style="vertical-align:middle;">
                                    <table role="presentation" width="44" cellpadding="0" cellspacing="0" border="0"
                                           style="width:44px; height:44px; background-color:#FFF9F4; border-radius:22px;">
                                        <tr>
                                            <td align="center" height="44" style="height:44px; font-family:Georgia, 'Times New Roman', serif; font-size:21px; line-height:44px; font-weight:bold; color:#A6294B;">
                                                {{ mb_substr($siteName, 0, 1) }}
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td style="padding-left:12px; vertical-align:middle; font-family:Georgia, 'Times New Roman', serif; font-size:20px; font-weight:bold; letter-spacing:0.5px; color:#FFFFFF;">
                                    {{ $siteName }}
                                </td>
                                <td align="right" style="vertical-align:middle; font-family:Helvetica, Arial, sans-serif; font-size:10px; letter-spacing:2px; text-transform:uppercase; color:#F4CBD1;">
                                    {{ $operator }}
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Isi --}}
                <tr>
                    <td style="padding:32px 30px 28px;">
                        @hasSection('eyebrow')
                            <p style="margin:0 0 10px; font-family:Helvetica, Arial, sans-serif; font-size:11px; font-weight:bold; letter-spacing:2.4px; text-transform:uppercase; color:#A6294B;">
                                @yield('eyebrow')
                            </p>
                        @endif

                        <h1 style="margin:0 0 14px; font-family:Georgia, 'Times New Roman', serif; font-size:25px; line-height:1.3; font-weight:normal; color:#1C1917;">
                            @yield('heading')
                        </h1>

                        @yield('content')
                    </td>
                </tr>

                {{-- Kaki: identitas usaha, alamat, dan kanal kontak. --}}
                <tr>
                    <td style="background-color:#FBF5EC; border-top:1px solid #EADFCF; padding:22px 30px;">
                        <p style="margin:0 0 6px; font-family:Helvetica, Arial, sans-serif; font-size:12px; font-weight:bold; letter-spacing:1.4px; text-transform:uppercase; color:#8A7E71;">
                            {{ $operator }}
                        </p>
                        <p style="margin:0; font-family:Helvetica, Arial, sans-serif; font-size:12px; line-height:1.7; color:#8A7E71;">
                            @foreach ($addressLines as $line){{ $line }}@if (! $loop->last)<br>@endif @endforeach
                            <br>
                            <a href="mailto:{{ $supportEmail }}" style="color:#A6294B; text-decoration:none;">{{ $supportEmail }}</a>
                            &middot; {{ $supportPhone }}
                            &middot; <a href="{{ $homeUrl }}" style="color:#A6294B; text-decoration:none;">{{ parse_url($homeUrl, PHP_URL_HOST) }}</a>
                        </p>
                        <p style="margin:10px 0 0; font-family:Helvetica, Arial, sans-serif; font-size:11px; line-height:1.6; color:#A79C8E;">
                            @yield('footer-note', 'Email ini dikirim otomatis oleh '.$siteName.' karena ada aktivitas pada akun atau pesanan Anda. Balas email ini bila perlu bantuan.')
                        </p>
                    </td>
                </tr>
            </table>

        </td>
    </tr>
</table>

</body>
</html>
