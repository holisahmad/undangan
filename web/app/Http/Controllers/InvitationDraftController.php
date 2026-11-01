<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Invitations\SaveInvitationDraft;
use App\Http\Requests\InvitationDraftRequest;
use App\Models\Wedding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final class InvitationDraftController extends Controller
{
    public function update(
        InvitationDraftRequest $request,
        Wedding $wedding,
        SaveInvitationDraft $saveDraft,
    ): RedirectResponse|JsonResponse {
        try {
            $saveDraft->handle($wedding, $request->templateVersion(), $request->content());
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (InvalidArgumentException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()->withInput()->withErrors(['content' => $exception->getMessage()]);
        }

        // The editor autosaves with fetch(); a JSON reply keeps the page and its
        // scroll position intact (no redirect round trip).
        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'saved',
                'saved_at' => now()->format('H:i'),
            ]);
        }

        return redirect()
            ->route('weddings.edit', $wedding)
            ->with('status', 'Draft disimpan. Tautan publik belum berubah sampai Anda menerbitkan ulang.');
    }
}
