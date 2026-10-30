<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Invitations\PublishInvitation;
use App\Actions\Invitations\UnpublishInvitation;
use App\Exceptions\InvitationNotPublishable;
use App\Models\Wedding;
use Illuminate\Http\RedirectResponse;

final class InvitationPublicationController extends Controller
{
    public function store(Wedding $wedding, PublishInvitation $publish): RedirectResponse
    {
        $this->authorize('publish', $wedding);

        try {
            $publication = $publish->handle($wedding, request()->user());
        } catch (InvitationNotPublishable $exception) {
            return back()->withErrors(['publish' => $exception->getMessage()]);
        }

        return redirect()
            ->route('weddings.show', $wedding)
            ->with('status', "Undangan diterbitkan (versi {$publication->version_number}).");
    }

    public function destroy(Wedding $wedding, UnpublishInvitation $unpublish): RedirectResponse
    {
        $this->authorize('unpublish', $wedding);

        $unpublish->handle($wedding, request()->user());

        return redirect()
            ->route('weddings.show', $wedding)
            ->with('status', 'Undangan dinonaktifkan. Tautan publik tidak lagi menampilkan undangan.');
    }
}
