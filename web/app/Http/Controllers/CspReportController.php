<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Collects CSP violation reports while the policy runs in report-only mode.
 * Only the violation fields are kept, and each is truncated: a report can
 * otherwise carry a full URL with personal data in its query string.
 */
final class CspReportController extends Controller
{
    public function __invoke(Request $request): Response
    {
        if (! (bool) config('platform.security.csp_report_only')) {
            return response()->noContent();
        }

        $payload = $request->json()->all();
        $report = $payload['csp-report'] ?? (is_array($payload) ? ($payload[0] ?? []) : []);

        if (! is_array($report)) {
            return response()->noContent();
        }

        Log::warning('content_security_policy.violation', [
            'document_uri' => Str::limit((string) ($report['document-uri'] ?? ''), 200),
            'violated_directive' => Str::limit((string) ($report['violated-directive'] ?? ''), 120),
            'blocked_uri' => Str::limit((string) ($report['blocked-uri'] ?? ''), 200),
            'source_file' => Str::limit((string) ($report['source-file'] ?? ''), 200),
        ]);

        return response()->noContent();
    }
}
