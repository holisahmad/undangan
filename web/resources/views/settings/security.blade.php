@extends('layouts.app')

@section('title', 'Keamanan akun')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <header>
            <h1 class="font-serif text-2xl font-semibold">Keamanan akun</h1>
            <p class="mt-1 text-sm text-stone-600">
                Autentikasi dua faktor (TOTP) meminta kode 6 digit dari aplikasi autentikator
                setiap kali akun ini masuk, di samping kata sandi.
            </p>
        </header>

        @if ($required && ! $user->hasTwoFactorEnabled())
            <p class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="alert">
                Operator wajib mengaktifkan autentikasi dua faktor. Panel admin tetap terkunci
                sampai langkah di bawah selesai.
            </p>
        @endif

        @if ($recoveryCodes !== [])
            <section class="rounded-md border border-emerald-300 bg-emerald-50 p-4" aria-labelledby="recovery-heading">
                <h2 id="recovery-heading" class="text-sm font-semibold text-emerald-900">Kode pemulihan</h2>
                <p class="mt-1 text-sm text-emerald-900">
                    Simpan sekarang. Kode ini hanya ditampilkan sekali dan masing-masing hanya bisa dipakai satu kali.
                </p>
                <ul class="mt-3 grid gap-2 font-mono text-sm sm:grid-cols-2">
                    @foreach ($recoveryCodes as $code)
                        <li class="rounded bg-white px-3 py-2">{{ $code }}</li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($user->hasTwoFactorEnabled())
            <section class="rounded-md border border-stone-200 bg-white p-4" aria-labelledby="enabled-heading">
                <h2 id="enabled-heading" class="text-sm font-semibold">Aktif</h2>
                <p class="mt-1 text-sm text-stone-600">
                    Aktif sejak {{ $user->two_factor_confirmed_at?->format('d M Y') }}.
                    Kode pemulihan tersisa: {{ count($user->twoFactorRecoveryHashes()) }}.
                </p>

                <form method="POST" action="{{ route('security.destroy') }}" class="mt-4 space-y-3">
                    @csrf
                    @method('DELETE')

                    <div>
                        <label for="password" class="block text-sm font-medium">Kata sandi</label>
                        <input id="password" name="password" type="password" required autocomplete="current-password"
                               class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm">
                    </div>

                    <div>
                        <label for="code" class="block text-sm font-medium">Kode autentikator atau kode pemulihan</label>
                        <input id="code" name="code" type="text" required autocomplete="one-time-code"
                               class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm">
                    </div>

                    <button type="submit" class="rounded-md border border-rose-300 px-4 py-2 text-sm font-medium text-rose-800 hover:bg-rose-50">
                        Matikan autentikasi dua faktor
                    </button>
                </form>
            </section>
        @else
            <section class="rounded-md border border-stone-200 bg-white p-4" aria-labelledby="setup-heading">
                <h2 id="setup-heading" class="text-sm font-semibold">Aktifkan</h2>
                <ol class="mt-2 list-decimal space-y-1 pl-5 text-sm text-stone-600">
                    <li>Buka aplikasi autentikator (Google Authenticator, Aegis, 1Password, dan sejenisnya).</li>
                    <li>Tambahkan akun baru dengan kunci manual di bawah (aplikasi ini tidak menampilkan kode QR).</li>
                    <li>Masukkan kode 6 digit yang muncul untuk mengonfirmasi.</li>
                </ol>

                <dl class="mt-4 space-y-2 text-sm">
                    <div>
                        <dt class="font-medium text-stone-700">Kunci manual</dt>
                        <dd class="mt-1 break-all rounded bg-stone-100 px-3 py-2 font-mono">{{ $pendingSecret }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-stone-700">URI otpauth</dt>
                        <dd class="mt-1 break-all rounded bg-stone-100 px-3 py-2 font-mono text-xs">{{ $provisioningUri }}</dd>
                    </div>
                </dl>

                <form method="POST" action="{{ route('security.confirm') }}" class="mt-4 space-y-3">
                    @csrf

                    <div>
                        <label for="code" class="block text-sm font-medium">Kode 6 digit</label>
                        <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code"
                               required pattern="[0-9 ]{6,10}" autofocus
                               class="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 text-sm shadow-sm">
                    </div>

                    <button type="submit" class="rounded-md bg-stone-900 px-4 py-2 text-sm font-medium text-white hover:bg-stone-800">
                        Aktifkan
                    </button>
                </form>
            </section>
        @endif
    </div>
@endsection
