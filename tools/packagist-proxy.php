<?php

/**
 * Dev-only Packagist metadata bridge.
 *
 * This machine can reach packagist.org but not repo.packagist.org, which is the
 * host Packagist publishes as its absolute `metadata-url`. Composer therefore
 * cannot read package metadata over the default repository.
 *
 * This router republishes the official metadata with a relative `metadata-url`
 * so Composer resolves against a local origin. Package archives are still
 * downloaded from their original (GitHub) dist URLs, so package contents are
 * not proxied or rewritten.
 *
 * Usage (see docs/adr/004-foundation-stack.md):
 *   php -S 127.0.0.1:8787 tools/packagist-proxy.php
 *   COMPOSER_HOME=<repo>/.local/composer composer install
 *
 * Do not expose this outside local development.
 */

declare(strict_types=1);

const UPSTREAM = 'https://packagist.org';
const DEFAULT_CACHE_TTL_SECONDS = 3600;

$cacheDir = __DIR__ . '/.cache/packagist';
$cacheTtl = (int) (getenv('PACKAGIST_PROXY_CACHE_TTL') ?: DEFAULT_CACHE_TTL_SECONDS);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if ($path === '/packages.json') {
    $body = fetch($cacheTtl, UPSTREAM . '/packages.json', $cacheDir);

    if ($body === null) {
        http_response_code(502);
        echo '{"error":"upstream unavailable"}';

        return;
    }

    $payload = json_decode($body, true);

    if (! is_array($payload)) {
        http_response_code(502);
        echo '{"error":"invalid upstream payload"}';

        return;
    }

    $payload['metadata-url'] = '/p2/%package%.json';
    unset($payload['providers-url'], $payload['providers-api'], $payload['list'], $payload['metadata-changes-url']);

    respond((string) json_encode($payload));

    return;
}

if (preg_match('#^/p2/[A-Za-z0-9_.-]+/[A-Za-z0-9_.~-]+\.json$#', $path) === 1) {
    $body = fetch($cacheTtl, UPSTREAM . $path, $cacheDir);

    if ($body === null) {
        http_response_code(502);
        echo '{"error":"upstream unavailable"}';

        return;
    }

    respond($body);

    return;
}

http_response_code(404);
echo '{"error":"not found"}';

function respond(string $body): void
{
    header('Content-Type: application/json');
    header('Content-Length: ' . strlen($body));
    echo $body;
}

function fetch(int $cacheTtl, string $url, string $cacheDir): ?string
{
    $cacheFile = $cacheDir . '/' . sha1($url) . '.json';

    if ($cacheTtl > 0 && is_file($cacheFile) && (time() - (int) filemtime($cacheFile)) < $cacheTtl) {
        $cached = file_get_contents($cacheFile);

        if ($cached !== false) {
            return $cached;
        }
    }

    $handle = curl_init($url);
    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 20,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_USERAGENT => 'undangan-packagist-proxy/1.0',
    ]);

    $body = curl_exec($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    curl_close($handle);

    if (! is_string($body) || $status !== 200) {
        return null;
    }

    if (! is_dir($cacheDir)) {
        mkdir($cacheDir, 0775, true);
    }

    file_put_contents($cacheFile, $body);

    return $body;
}
