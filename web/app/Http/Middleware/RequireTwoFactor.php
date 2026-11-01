<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Optional enforcement of admin 2FA (ADMIN_REQUIRE_TWO_FACTOR). It is off by
 * default so a fresh deployment is not locked out, but production is expected
 * to switch it on (docs/SECURITY.md, section 8).
 */
final class RequireTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! (bool) config('platform.security.require_admin_two_factor')) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user instanceof User || ! $user->isAdmin() || $user->hasTwoFactorEnabled()) {
            return $next($request);
        }

        if ($request->routeIs('security.*') || $request->routeIs('logout')) {
            return $next($request);
        }

        return redirect()->route('security.edit')
            ->with('status', 'Operator wajib mengaktifkan autentikasi dua faktor sebelum membuka panel admin.');
    }
}
