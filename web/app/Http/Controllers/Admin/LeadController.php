<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Support\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Incoming enquiries from the public site. Marking a lead as contacted is the
 * only state change; the enquiry itself is never edited or deleted here.
 */
final class LeadController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('q')->toString());
        $status = $request->string('status')->toString();

        $leads = Lead::query()
            ->when($search !== '', static function ($query) use ($search): void {
                $query->where(static function ($query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('whatsapp', 'like', '%'.$search.'%');
                });
            })
            ->when(in_array($status, [Lead::STATUS_NEW, Lead::STATUS_CONTACTED], true),
                static fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.leads.index', [
            'leads' => $leads,
            'search' => $search,
            'status' => $status,
            'newCount' => Lead::query()->where('status', Lead::STATUS_NEW)->count(),
        ]);
    }

    public function contacted(Request $request, Lead $lead, AuditLogger $audit): RedirectResponse
    {
        if ($lead->isNew()) {
            $lead->forceFill(['status' => Lead::STATUS_CONTACTED])->save();

            $audit->log('lead.contacted', 'lead', (string) $lead->getKey(), $request->user());
        }

        return back()->with('status', 'Lead ditandai sudah dihubungi.');
    }
}
