<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Wedding;
use App\Support\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Owner-controlled portfolio opt-in. Enabling requires a live publication so a
 * portfolio entry always shows something real; disabling is immediate.
 */
final class WeddingShowcaseController extends Controller
{
    public function update(Request $request, Wedding $wedding, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('update', $wedding);

        $validated = $request->validate([
            'showcase_enabled' => ['required', 'boolean'],
        ]);

        $enable = (bool) $validated['showcase_enabled'];

        if ($enable && $wedding->activePublication()->first() === null) {
            return back()->withErrors([
                'showcase' => 'Terbitkan undangan terlebih dahulu sebelum menampilkannya di halaman inspirasi.',
            ]);
        }

        if ($wedding->showcase_enabled === $enable) {
            return back()->with('status', 'Pengaturan portofolio tidak berubah.');
        }

        $wedding->forceFill([
            'showcase_enabled' => $enable,
            'showcase_enabled_at' => $enable ? now() : null,
        ])->save();

        $audit->log(
            action: $enable ? 'wedding.showcase_enabled' : 'wedding.showcase_disabled',
            resourceType: 'wedding',
            resourceId: (string) $wedding->getKey(),
            actor: $request->user(),
        );

        return back()->with('status', $enable
            ? 'Undangan tampil di halaman inspirasi. Anda dapat menariknya kapan saja.'
            : 'Undangan tidak lagi tampil di halaman inspirasi.');
    }
}
