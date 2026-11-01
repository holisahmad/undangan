<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit\AuditLogger;
use App\Support\Security\RecoveryCodes;
use App\Support\Security\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Second step of the login flow for accounts with TOTP enabled. The password
 * step has already proven the first factor but deliberately does not start a
 * session (docs/SECURITY.md, section 8).
 */
final class TwoFactorChallengeController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if ($this->pendingUser($request) === null) {
            return redirect()->route('login');
        }

        return view('auth.two-factor');
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $user = $this->pendingUser($request);

        if ($user === null) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:10'],
            'recovery_code' => ['nullable', 'string', 'max:24'],
        ]);

        $key = $this->throttleKey($request, $user);
        $limit = (int) config('platform.security.two_factor_rate_limit_per_minute', 5);

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'code' => trans('auth.throttle', ['seconds' => $seconds, 'minutes' => (int) ceil($seconds / 60)]),
            ]);
        }

        $recoveryCode = trim((string) ($validated['recovery_code'] ?? ''));
        $code = trim((string) ($validated['code'] ?? ''));
        $passed = false;
        $method = 'totp';

        if ($recoveryCode !== '') {
            $consumed = RecoveryCodes::consume($user->twoFactorRecoveryHashes(), $recoveryCode);

            if ($consumed['matched']) {
                $user->forceFill(['two_factor_recovery_codes' => $consumed['remaining']])->save();
                $passed = true;
                $method = 'recovery_code';
            }
        } elseif (Totp::verify((string) $user->two_factor_secret, $code)) {
            $passed = true;
        }

        if (! $passed) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'code' => 'Kode tidak valid atau sudah kedaluwarsa.',
            ]);
        }

        RateLimiter::clear($key);
        $remember = (bool) $request->session()->pull('two_factor.remember', false);

        Auth::guard('web')->login($user, $remember);
        $request->session()->forget('two_factor.user_id');
        $request->session()->regenerate();

        $audit->log(
            action: 'auth.two_factor_passed',
            resourceType: 'user',
            resourceId: (string) $user->getKey(),
            actor: $user,
            metadata: ['method' => $method],
        );

        return redirect()->intended(route('dashboard'));
    }

    private function pendingUser(Request $request): ?User
    {
        $id = $request->session()->get('two_factor.user_id');

        if (! is_int($id) && ! is_string($id)) {
            return null;
        }

        $user = User::query()->find($id);

        if (! $user instanceof User || $user->isSuspended() || ! $user->hasTwoFactorEnabled()) {
            return null;
        }

        return $user;
    }

    private function throttleKey(Request $request, User $user): string
    {
        return 'two-factor:login|'.$user->getKey().'|'.$request->ip();
    }
}
