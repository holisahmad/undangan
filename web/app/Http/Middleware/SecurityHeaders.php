<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Conservative response headers for every web route. Media is served from
 * signed URLs, so no route needs to be framed or sniffed by a browser.
 */
final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // The policy ships as report-only because templates still carry inline
        // scripts, and enforcing it too early would break pages
        // (docs/SECURITY.md, section 9). Switching the flag off therefore has to
        // enforce the same policy: dropping the header entirely would turn the
        // documented hardening step into a removal of protection.
        $reportOnly = (bool) config('platform.security.csp_report_only');
        $policy = (string) config('platform.security.csp_policy');

        if ($policy !== '') {
            $headers->set(
                $reportOnly ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy',
                $policy,
            );
        }

        if ($reportOnly) {
            $headers->set('Reporting-Endpoints', 'csp="/csp-reports"');
        }

        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
