<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Invitations\UnpublishInvitation;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Wedding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Operator area. Access is granted by the `access-admin` gate only; every
 * action keeps the owner's data untouched and is written to the audit log.
 */
final class WeddingController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('q')->toString());

        $weddings = Wedding::query()
            ->with(['owner', 'activePublication'])
            ->when($search !== '', static function ($query) use ($search): void {
                $query->where(static function ($query) use ($search): void {
                    $query->where('internal_title', 'like', '%'.$search.'%')
                        ->orWhere('slug', 'like', '%'.$search.'%')
                        ->orWhereHas('owner', static fn ($owner) => $owner->where('email', 'like', '%'.$search.'%'));
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.weddings.index', [
            'weddings' => $weddings,
            'search' => $search,
        ]);
    }

    public function show(Wedding $wedding): View
    {
        $wedding->load(['owner', 'partners', 'events', 'activePublication', 'latestPublication']);

        return view('admin.weddings.show', [
            'wedding' => $wedding,
            'entitlement' => $wedding->activeInvitationEntitlement(),
            'auditLogs' => AuditLog::query()
                ->with('actor')
                ->where('resource_type', 'wedding')
                ->where('resource_id', $wedding->getKey())
                ->latest()
                ->limit(20)
                ->get(),
        ]);
    }

    public function suspend(Request $request, Wedding $wedding, UnpublishInvitation $unpublish): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:4', 'max:200'],
        ]);

        $unpublish->handle($wedding, $request->user(), $validated['reason']);

        return back()->with('status', 'Publikasi undangan dinonaktifkan dan keputusan ini tercatat di audit log.');
    }
}
