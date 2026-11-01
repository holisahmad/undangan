<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/**
 * Served dynamically so the sitemap URL always matches APP_URL. The static
 * file it replaces could not advertise a host that differs per environment.
 */
final class RobotsController extends Controller
{
    /**
     * @var list<string>
     */
    private const DISALLOW = [
        '/admin',
        '/dashboard',
        '/weddings',
        '/orders',
        '/checkout',
        '/g/',
        '/u/',
        '/media/',
    ];

    public function __invoke(): Response
    {
        $lines = ['User-agent: *', 'Allow: /'];

        foreach (self::DISALLOW as $path) {
            $lines[] = 'Disallow: '.$path;
        }

        $lines[] = '';
        $lines[] = 'Sitemap: '.route('sitemap');

        return response(implode("\n", $lines)."\n", 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
