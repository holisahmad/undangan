<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Applies to every response, including the /up health check.
        $middleware->append(SecurityHeaders::class);

        // A suspended account is logged out on its next request.
        $middleware->web(append: [EnsureAccountIsActive::class]);

        // Provider callbacks and browser CSP reports cannot send a CSRF token;
        // the former is authenticated by signature, the latter is write-only log
        // input capped at the route level.
        $middleware->validateCsrfTokens(except: [
            'payments/webhook/*',
            'csp-reports',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // PHP rejects an over-sized body before the session and the auth guard
        // exist. Logging it keeps the operator able to see how often the host
        // limits bite; resources/views/errors/413.blade.php answers the browser.
        $exceptions->render(function (PostTooLargeException $exception, Request $request) {
            Log::warning('request.post_too_large', [
                'path' => $request->path(),
                'content_length' => $request->header('Content-Length'),
                'post_max_size' => (string) ini_get('post_max_size'),
                'upload_max_filesize' => (string) ini_get('upload_max_filesize'),
            ]);

            return null;
        });
    })->create();
