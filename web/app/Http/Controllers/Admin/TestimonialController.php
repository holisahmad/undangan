<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use App\Support\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Moderation queue for testimonials. Only approved quotes are rendered on the
 * landing page; rejecting keeps the row so the decision stays auditable.
 */
final class TestimonialController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();

        $testimonials = Testimonial::query()
            ->with(['wedding', 'user'])
            ->when(in_array($status, [
                Testimonial::STATUS_PENDING,
                Testimonial::STATUS_APPROVED,
                Testimonial::STATUS_REJECTED,
            ], true), static fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.testimonials.index', [
            'testimonials' => $testimonials,
            'status' => $status,
            'pendingCount' => Testimonial::query()->where('status', Testimonial::STATUS_PENDING)->count(),
        ]);
    }

    public function approve(Request $request, Testimonial $testimonial, AuditLogger $audit): RedirectResponse
    {
        return $this->moderate($request, $testimonial, $audit, Testimonial::STATUS_APPROVED);
    }

    public function reject(Request $request, Testimonial $testimonial, AuditLogger $audit): RedirectResponse
    {
        return $this->moderate($request, $testimonial, $audit, Testimonial::STATUS_REJECTED);
    }

    private function moderate(
        Request $request,
        Testimonial $testimonial,
        AuditLogger $audit,
        string $status,
    ): RedirectResponse {
        $testimonial->forceFill([
            'status' => $status,
            'moderated_at' => now(),
        ])->save();

        $audit->log(
            action: $status === Testimonial::STATUS_APPROVED ? 'testimonial.approved' : 'testimonial.rejected',
            resourceType: 'testimonial',
            resourceId: (string) $testimonial->getKey(),
            actor: $request->user(),
            metadata: ['wedding_id' => $testimonial->wedding_id],
        );

        return back()->with('status', $status === Testimonial::STATUS_APPROVED
            ? 'Testimoni disetujui dan tampil di landing page.'
            : 'Testimoni ditolak dan tidak ditampilkan.');
    }
}
