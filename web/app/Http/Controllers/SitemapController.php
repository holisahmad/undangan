<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\InvitationTemplate;
use App\Models\Wedding;
use Illuminate\Http\Response;

/**
 * XML sitemap for the public, indexable surface only. Guest links (/g/),
 * published invitations (/u/) and authenticated areas are excluded on purpose:
 * they are personalised or private (docs/SECURITY.md).
 */
final class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $templates = InvitationTemplate::query()
            ->where('status', InvitationTemplate::STATUS_ACTIVE)
            ->orderBy('slug')
            ->get(['slug', 'updated_at']);

        // Portfolio pages are public and indexable by the owner's own choice;
        // regular invitations (/u/) stay out of the sitemap on purpose.
        $showcase = Wedding::query()
            ->where('showcase_enabled', true)
            ->whereHas('activePublication')
            ->orderBy('slug')
            ->get(['slug', 'updated_at']);

        return response()
            ->view('seo.sitemap', [
                'home' => route('home'),
                'themes' => route('templates.index'),
                'login' => route('login'),
                'register' => route('register'),
                'privacy' => route('legal.privacy'),
                'terms' => route('legal.terms'),
                'templates' => $templates,
                'showcase' => $showcase,
                'lastModified' => $templates->max('updated_at') ?? now(),
            ])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
