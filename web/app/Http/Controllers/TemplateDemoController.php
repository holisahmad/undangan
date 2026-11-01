<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\InvitationTemplate;
use App\Support\Invitations\SampleInvitation;
use App\Support\Templates\TemplateRegistry;
use Illuminate\View\View;

/**
 * Public, indexable preview of every shipped template. Uses fictional sample
 * data so prospective customers can judge a design before registering, and
 * never exposes a real couple's publication.
 */
final class TemplateDemoController extends Controller
{
    public function show(InvitationTemplate $template, TemplateRegistry $registry): View
    {
        abort_unless($template->status === InvitationTemplate::STATUS_ACTIVE, 404);

        $version = $template->publishedVersion();

        if ($version === null || ! $registry->supports($version->renderer_key)) {
            abort(404);
        }

        return view($registry->viewFor($version->renderer_key), [
            'invitation' => SampleInvitation::build($template, $version),
            'preview' => false,
            'demo' => true,
            'guest' => null,
            'rsvp' => null,
            'token' => null,
            'hasGallery' => false,
            'galleryUrl' => null,
        ]);
    }
}
