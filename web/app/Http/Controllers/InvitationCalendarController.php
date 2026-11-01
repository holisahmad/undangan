<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\PublicInvitation;
use App\Models\InvitationTemplate;
use App\Models\Wedding;
use App\Support\Invitations\CalendarFile;
use App\Support\Invitations\SampleInvitation;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Serves the calendar file behind "Simpan tanggal".
 *
 * It reads the same publication snapshot as the invitation page, so a guest
 * can only download the schedule that is currently published, and a demo keeps
 * working before any customer exists.
 */
final class InvitationCalendarController extends Controller
{
    public function show(string $slug): Response
    {
        $wedding = Wedding::query()->where('slug', $slug)->first();

        abort_if($wedding === null, 404);

        $publication = $wedding->activePublication()->first();

        abort_if($publication === null, 410);

        return $this->download(
            PublicInvitation::fromSnapshot($wedding->slug, $publication->published_snapshot),
        );
    }

    public function demo(InvitationTemplate $template): Response
    {
        abort_unless($template->status === InvitationTemplate::STATUS_ACTIVE, 404);

        $version = $template->publishedVersion();

        abort_if($version === null, 404);

        return $this->download(SampleInvitation::build($template, $version));
    }

    private function download(PublicInvitation $invitation): Response
    {
        $body = CalendarFile::build($invitation);

        abort_if($body === null, 404);

        $filename = Str::slug($invitation->coupleHeadline() ?: 'undangan').'.ics';

        return response($body, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }
}
