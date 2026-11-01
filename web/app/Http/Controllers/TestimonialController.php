<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Testimonial;
use App\Models\Wedding;
use App\Support\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The couple writes one testimonial per wedding. Editing it puts it back into
 * moderation so an old approval can never publish new words silently.
 */
final class TestimonialController extends Controller
{
    public function store(Request $request, Wedding $wedding, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('update', $wedding);

        if ($wedding->activePublication()->first() === null) {
            return back()->withErrors([
                'testimonial' => 'Testimoni dapat dikirim setelah undangan diterbitkan.',
            ]);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'quote' => ['required', 'string', 'min:20', 'max:500'],
        ], [
            'quote.min' => 'Tulis minimal 20 karakter agar testimoni cukup menjelaskan pengalaman Anda.',
        ]);

        $testimonial = Testimonial::updateOrCreate(
            ['wedding_id' => $wedding->getKey()],
            [
                'user_id' => (string) $request->user()->getKey(),
                'rating' => (int) $validated['rating'],
                'quote' => trim($validated['quote']),
                'status' => Testimonial::STATUS_PENDING,
                'moderated_at' => null,
            ],
        );

        $audit->log(
            action: 'testimonial.submitted',
            resourceType: 'testimonial',
            resourceId: (string) $testimonial->getKey(),
            actor: $request->user(),
            metadata: [
                'wedding_id' => $wedding->getKey(),
                'rating' => (int) $validated['rating'],
            ],
        );

        return back()->with('status', 'Terima kasih. Testimoni Anda menunggu peninjauan sebelum tampil.');
    }
}
