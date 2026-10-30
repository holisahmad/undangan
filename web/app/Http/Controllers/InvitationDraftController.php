<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Invitations\SaveInvitationDraft;
use App\Http\Requests\InvitationDraftRequest;
use App\Models\Wedding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final class InvitationDraftController extends Controller
{
    public function update(
        InvitationDraftRequest $request,
        Wedding $wedding,
        SaveInvitationDraft $saveDraft,
    ): RedirectResponse {
        try {
            $saveDraft->handle($wedding, $request->templateVersion(), $request->content());
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['content' => $exception->getMessage()]);
        }

        return redirect()
            ->route('weddings.edit', $wedding)
            ->with('status', 'Draft disimpan. Tautan publik belum berubah sampai Anda menerbitkan ulang.');
    }
}
