<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only trail of sensitive actions. Filters are inclusive so an operator
 * can narrow a day-by-day investigation without losing the boundary records.
 */
final class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $action = trim($request->string('action')->toString());
        $resourceType = trim($request->string('resource_type')->toString());
        $actor = trim($request->string('actor')->toString());
        $from = $request->date('from');
        $to = $request->date('to');

        $logs = AuditLog::query()
            ->with('actor')
            ->when($action !== '', static fn ($query) => $query->where('action', $action))
            ->when($resourceType !== '', static fn ($query) => $query->where('resource_type', $resourceType))
            ->when($actor !== '', static function ($query) use ($actor): void {
                $query->whereHas('actor', static fn ($query) => $query
                    ->where('email', 'like', '%'.$actor.'%')
                    ->orWhere('name', 'like', '%'.$actor.'%'));
            })
            ->when($from !== null, static fn ($query) => $query->where('created_at', '>=', $from->startOfDay()))
            ->when($to !== null, static fn ($query) => $query->where('created_at', '<=', $to->endOfDay()))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'action' => $action,
            'resourceType' => $resourceType,
            'actor' => $actor,
            'from' => $from?->toDateString() ?? '',
            'to' => $to?->toDateString() ?? '',
            'actions' => AuditLog::query()
                ->select('action')
                ->distinct()
                ->orderBy('action')
                ->pluck('action'),
            'resourceTypes' => AuditLog::query()
                ->select('resource_type')
                ->distinct()
                ->orderBy('resource_type')
                ->pluck('resource_type'),
        ]);
    }
}
