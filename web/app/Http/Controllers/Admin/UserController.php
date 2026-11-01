<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\AccountSuspendedNotification;
use App\Support\Audit\AuditLogger;
use App\Support\Csv\CsvSanitizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Customer directory for the operator. Suspension hides nothing from the owner
 * and deletes nothing: it blocks sign-in and ends live sessions until lifted.
 */
final class UserController extends Controller
{
    /**
     * Segments are query shortcuts over data the operator already owns; they
     * are never stored on the account (docs/PRIVACY_AND_RETENTION.md).
     */
    public const SEGMENTS = [
        'new' => 'Baru (30 hari)',
        'published' => 'Sudah terbit',
        'showcase' => 'Tampil di inspirasi',
        'no_weddings' => 'Belum punya undangan',
    ];

    /**
     * Bounds for the tag dropdown. It is a filter convenience, not a report, so
     * it never reads the whole customer table on a page load.
     */
    private const TAG_SCAN_LIMIT = 2000;

    private const TAG_OPTION_LIMIT = 200;

    private const TAG_CACHE_MINUTES = 10;

    private const TAG_CACHE_KEY = 'admin.users.tag-options';

    public function index(Request $request): View
    {
        [$search, $role, $status, $tag, $segment] = $this->filters($request);

        $users = $this->filtered($search, $role, $status, $tag, $segment)
            ->withCount(['weddings', 'orders'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'search' => $search,
            'role' => $role,
            'status' => $status,
            'tag' => $tag,
            'segment' => $segment,
            'segments' => self::SEGMENTS,
            'tagOptions' => $this->tagOptions(),
        ]);
    }

    /**
     * Campaign list: the same filters as the directory, exported as CSV with
     * formula injection neutralised (docs/SECURITY.md, section 4). Only fields
     * the operator already sees in the panel are written.
     */
    public function export(Request $request, AuditLogger $audit): StreamedResponse
    {
        [$search, $role, $status, $tag, $segment] = $this->filters($request);

        $query = $this->filtered($search, $role, $status, $tag, $segment)
            ->withCount(['weddings', 'orders'])
            ->orderBy('created_at');

        $count = (clone $query)->count();

        $audit->log('user.exported', 'user', null, $request->user(), [
            'rows' => $count,
            'role' => $role !== '' ? $role : null,
            'status' => $status !== '' ? $status : null,
            'tag' => $tag !== '' ? $tag : null,
            'segment' => $segment !== '' ? $segment : null,
        ]);

        $filename = 'pelanggan-'.now()->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'wb');

            if ($out === false) {
                return;
            }

            fwrite($out, "\xEF\xBB\xBF");

            fputcsv(
                $out,
                ['name', 'email', 'role', 'status', 'tags', 'note', 'weddings', 'orders', 'registered_at'],
                ',',
                '"',
                '',
            );

            $query->chunk(200, function ($users) use ($out): void {
                foreach ($users as $user) {
                    fputcsv($out, [
                        CsvSanitizer::cell($user->name),
                        CsvSanitizer::cell($user->email),
                        $user->role->value,
                        $user->isSuspended() ? 'suspended' : 'active',
                        CsvSanitizer::cell(implode(', ', $user->crm_tags ?? [])),
                        CsvSanitizer::cell($user->crm_note),
                        (string) $user->weddings_count,
                        (string) $user->orders_count,
                        $user->created_at?->toIso8601String() ?? '',
                    ], ',', '"', '');
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Internal note and tags. The note itself stays out of the audit metadata
     * because it may contain personal data; only its presence is recorded.
     */
    public function updateCrm(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'crm_note' => ['nullable', 'string', 'max:1000'],
            'crm_tags' => ['nullable', 'string', 'max:300'],
        ]);

        $note = trim((string) ($validated['crm_note'] ?? ''));

        $tags = collect(explode(',', (string) ($validated['crm_tags'] ?? '')))
            ->map(static fn (string $tag): string => mb_strtolower(trim($tag)))
            ->filter(static fn (string $tag): bool => $tag !== '')
            ->map(static fn (string $tag): string => mb_substr($tag, 0, 24))
            ->unique()
            ->take(10)
            ->values()
            ->all();

        $user->forceFill([
            'crm_note' => $note === '' ? null : $note,
            'crm_tags' => $tags === [] ? null : $tags,
        ])->save();

        $audit->log('user.crm_updated', 'user', (string) $user->getKey(), $request->user(), [
            'tags' => implode(',', $tags),
            'has_note' => $note !== '',
        ]);

        Cache::forget(self::TAG_CACHE_KEY);

        return back()->with('status', 'Catatan dan tag CRM diperbarui.');
    }

    public function show(User $user): View
    {
        $user->loadCount(['weddings', 'orders']);
        $user->load('testimonial.wedding');

        return view('admin.users.show', [
            'user' => $user,
            'tagOptions' => $this->tagOptions(),
            'weddings' => $user->weddings()->latest()->limit(10)->get(),
            'orders' => $user->orders()->with('items.plan')->latest()->limit(10)->get(),
            'auditLogs' => AuditLog::query()
                ->with('actor')
                ->where('resource_type', 'user')
                ->where('resource_id', (string) $user->getKey())
                ->latest()
                ->limit(20)
                ->get(),
        ]);
    }

    public function suspend(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:4', 'max:200'],
        ]);

        $this->ensureSuspendable($request, $user);

        if ($user->isSuspended()) {
            return back()->with('status', 'Akun ini sudah dinonaktifkan.');
        }

        $user->forceFill([
            'suspended_at' => now(),
            'suspension_reason' => $validated['reason'],
        ])->save();

        $audit->log('user.suspended', 'user', (string) $user->getKey(), $request->user(), [
            'reason' => $validated['reason'],
        ]);

        $this->notifySuspended($user, $validated['reason']);

        return back()->with('status', 'Akun dinonaktifkan. Sesi aktif akan berakhir pada permintaan berikutnya.');
    }

    public function activate(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        if (! $user->isSuspended()) {
            return back()->with('status', 'Akun ini sudah aktif.');
        }

        $user->forceFill([
            'suspended_at' => null,
            'suspension_reason' => null,
        ])->save();

        $audit->log('user.activated', 'user', (string) $user->getKey(), $request->user());

        return back()->with('status', 'Akun diaktifkan kembali. Pemilik dapat masuk seperti biasa.');
    }

    /**
     * The directory and the CSV export answer to the same five query knobs.
     *
     * @return array{0: string, 1: string, 2: string, 3: string, 4: string}
     */
    private function filters(Request $request): array
    {
        return [
            trim($request->string('q')->toString()),
            $request->string('role')->toString(),
            $request->string('status')->toString(),
            mb_strtolower(trim($request->string('tag')->toString())),
            $request->string('segment')->toString(),
        ];
    }

    /**
     * @return Builder<User>
     */
    private function filtered(
        string $search,
        string $role,
        string $status,
        string $tag,
        string $segment,
    ): Builder {
        return User::query()
            ->when($search !== '', static function ($query) use ($search): void {
                $query->where(static function ($query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->when(in_array($role, ['customer', 'admin'], true), static fn ($query) => $query->where('role', $role))
            ->when($status === 'suspended', static fn ($query) => $query->whereNotNull('suspended_at'))
            ->when($status === 'active', static fn ($query) => $query->whereNull('suspended_at'))
            ->when($tag !== '', static fn ($query) => $query->whereJsonContains('crm_tags', $tag))
            ->when($segment === 'new', static fn ($query) => $query->where('created_at', '>=', now()->subDays(30)))
            ->when($segment === 'published', static fn ($query) => $query->whereHas(
                'weddings',
                static fn ($query) => $query->whereHas('activePublication'),
            ))
            ->when($segment === 'showcase', static fn ($query) => $query->whereHas(
                'weddings',
                static fn ($query) => $query->where('showcase_enabled', true),
            ))
            ->when($segment === 'no_weddings', static fn ($query) => $query->whereDoesntHave('weddings'));
    }

    /**
     * @return list<string>
     */
    private function tagOptions(): array
    {
        // Tags live in a JSON column, so the list is assembled in PHP. Capping
        // the scan and caching the result keeps that cost flat as the customer
        // base grows instead of reading every account on every page load.
        return Cache::remember(
            self::TAG_CACHE_KEY,
            now()->addMinutes(self::TAG_CACHE_MINUTES),
            static fn (): array => User::query()
                ->whereNotNull('crm_tags')
                ->latest()
                ->limit(self::TAG_SCAN_LIMIT)
                ->pluck('crm_tags')
                ->flatMap(static fn ($tags): array => is_array($tags) ? $tags : [])
                ->unique()
                ->sort()
                ->take(self::TAG_OPTION_LIMIT)
                ->values()
                ->all(),
        );
    }

    private function ensureSuspendable(Request $request, User $user): void
    {
        abort_if($user->is($request->user()), 403, 'Anda tidak dapat menonaktifkan akun sendiri.');

        abort_if($user->isAdmin(), 403, 'Akun operator lain tidak dapat dinonaktifkan dari panel ini.');
    }

    /**
     * The suspension itself must not fail because the mailer is down, so a
     * delivery error is reported instead of surfacing to the operator.
     */
    private function notifySuspended(User $user, string $reason): void
    {
        try {
            $user->notify(new AccountSuspendedNotification($reason));
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
