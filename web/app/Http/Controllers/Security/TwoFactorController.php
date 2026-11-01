<?php

declare(strict_types=1);

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Support\Audit\AuditLogger;
use App\Support\Security\RecoveryCodes;
use App\Support\Security\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Self-service TOTP setup for operators and customers (docs/SECURITY.md,
 * section 8). A secret is only stored after one generated code is confirmed,
 * and recovery codes are shown exactly once.
 */
final class TwoFactorController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        $pending = $request->session()->get('two_factor.pending_secret');

        if (! $user->hasTwoFactorEnabled() && ! is_string($pending)) {
            $pending = Totp::generateSecret();
            $request->session()->put('two_factor.pending_secret', $pending);
        }

        $pendingSecret = $user->hasTwoFactorEnabled() || ! is_string($pending) ? null : $pending;

        return view('settings.security', [
            'user' => $user,
            'pendingSecret' => $pendingSecret,
            'provisioningUri' => $pendingSecret === null
                ? null
                : Totp::provisioningUri($pendingSecret, (string) $user->email, (string) config('app.name')),
            'recoveryCodes' => (array) $request->session()->get('two_factor.recovery_codes', []),
            'required' => (bool) config('platform.security.require_admin_two_factor') && $user->isAdmin(),
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function confirm(Request $request, AuditLogger $audit): RedirectResponse
    {
        $user = $request->user();
        $secret = $request->session()->get('two_factor.pending_secret');

        if (! is_string($secret) || $secret === '') {
            return redirect()->route('security.edit')
                ->withErrors(['code' => 'Sesi penyiapan berakhir. Muat ulang halaman untuk kode baru.']);
        }

        $this->ensureNotRateLimited($request, 'confirm');

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:10'],
        ]);

        if (! Totp::verify($secret, (string) $validated['code'])) {
            RateLimiter::hit($this->throttleKey($request, 'confirm'), 60);

            throw ValidationException::withMessages([
                'code' => 'Kode tidak cocok. Masukkan kode terbaru dari aplikasi autentikator.',
            ]);
        }

        $codes = RecoveryCodes::generate();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => RecoveryCodes::hashAll($codes),
            'two_factor_confirmed_at' => now(),
        ])->save();

        RateLimiter::clear($this->throttleKey($request, 'confirm'));
        $request->session()->forget('two_factor.pending_secret');
        $request->session()->flash('two_factor.recovery_codes', $codes);

        $audit->log(
            action: 'user.two_factor_enabled',
            resourceType: 'user',
            resourceId: (string) $user->getKey(),
            actor: $user,
            metadata: ['recovery_codes' => count($codes)],
        );

        return redirect()->route('security.edit')
            ->with('status', 'Autentikasi dua faktor aktif. Simpan kode pemulihan sebelum meninggalkan halaman ini.');
    }

    /**
     * @throws ValidationException
     */
    public function destroy(Request $request, AuditLogger $audit): RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasTwoFactorEnabled()) {
            return redirect()->route('security.edit');
        }

        $this->ensureNotRateLimited($request, 'disable');

        $validated = $request->validate([
            'password' => ['required', 'string', 'current_password'],
            'code' => ['required', 'string', 'max:10'],
        ]);

        $code = (string) $validated['code'];
        $consumed = RecoveryCodes::consume($user->twoFactorRecoveryHashes(), $code);
        $valid = Totp::verify((string) $user->two_factor_secret, $code) || $consumed['matched'];

        if (! $valid) {
            RateLimiter::hit($this->throttleKey($request, 'disable'), 60);

            throw ValidationException::withMessages([
                'code' => 'Kode autentikator atau kode pemulihan tidak valid.',
            ]);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        RateLimiter::clear($this->throttleKey($request, 'disable'));
        $request->session()->forget('two_factor.pending_secret');

        $audit->log(
            action: 'user.two_factor_disabled',
            resourceType: 'user',
            resourceId: (string) $user->getKey(),
            actor: $user,
            metadata: ['method' => $consumed['matched'] ? 'recovery_code' : 'totp'],
        );

        return redirect()->route('security.edit')->with('status', 'Autentikasi dua faktor dimatikan.');
    }

    /**
     * @throws ValidationException
     */
    private function ensureNotRateLimited(Request $request, string $action): void
    {
        $key = $this->throttleKey($request, $action);
        $limit = (int) config('platform.security.two_factor_rate_limit_per_minute', 5);

        if (! RateLimiter::tooManyAttempts($key, $limit)) {
            return;
        }

        $seconds = RateLimiter::availableIn($key);

        throw ValidationException::withMessages([
            'code' => trans('auth.throttle', ['seconds' => $seconds, 'minutes' => (int) ceil($seconds / 60)]),
        ]);
    }

    private function throttleKey(Request $request, string $action): string
    {
        return 'two-factor:'.$action.'|'.$request->user()?->getAuthIdentifier().'|'.$request->ip();
    }
}
