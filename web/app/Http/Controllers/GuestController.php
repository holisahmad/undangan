<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Guests\CreateGuest;
use App\Actions\Guests\DeleteGuest;
use App\Actions\Guests\UpdateGuest;
use App\Enums\RsvpStatus;
use App\Http\Requests\GuestRequest;
use App\Models\Guest;
use App\Models\GuestInvitationLink;
use App\Models\Rsvp;
use App\Models\Wedding;
use App\Support\Csv\CsvSanitizer;
use App\Support\Guests\GuestQuota;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class GuestController extends Controller
{
    public function index(Request $request, Wedding $wedding, GuestQuota $quota): View
    {
        $this->authorize('view', $wedding);

        $search = trim((string) $request->query('q', ''));
        $groupId = (string) $request->query('group', '');

        $query = $wedding->guests()
            ->with(['group', 'rsvp', 'activeInvitationLink', 'latestInvitationLink'])
            ->orderBy('display_name');

        if ($search !== '') {
            // Escape wildcards so a literal % or _ does not widen the match.
            $query->where('display_name', 'like', '%'.addcslashes($search, '%_\\').'%');
        }

        if ($groupId !== '') {
            $query->where('guest_group_id', $groupId);
        }

        $guestIds = $wedding->guests()->select('guests.id');

        return view('guests.index', [
            'wedding' => $wedding,
            'guests' => $query->paginate(20)->withQueryString(),
            'groups' => $wedding->guestGroups()->withCount('guests')->orderBy('name')->get(),
            'search' => $search,
            'groupId' => $groupId,
            'stats' => [
                'total' => $wedding->guests()->count(),
                'linked' => GuestInvitationLink::query()
                    ->whereIn('guest_id', $guestIds)
                    ->whereNull('revoked_at')
                    ->where(static fn ($builder) => $builder->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                    ->distinct()
                    ->count('guest_id'),
                'responded' => $wedding->guests()->whereHas('rsvp')->count(),
                'attending' => (int) Rsvp::query()
                    ->whereIn('guest_id', $guestIds)
                    ->where('status', RsvpStatus::Attending->value)
                    ->sum('party_size'),
            ],
            'quotaLimit' => $quota->limitFor($wedding),
            'quotaUsed' => $quota->usedFor($wedding),
        ]);
    }

    public function create(Wedding $wedding): View
    {
        $this->authorize('update', $wedding);

        return view('guests.create', [
            'wedding' => $wedding,
            'guest' => new Guest(['allowed_party_size' => 1]),
            'groups' => $wedding->guestGroups()->orderBy('name')->get(),
        ]);
    }

    public function store(GuestRequest $request, Wedding $wedding, CreateGuest $createGuest): RedirectResponse
    {
        $guest = $createGuest->handle($wedding, $request->user(), $request->guestData());

        return redirect()
            ->route('weddings.guests.index', $wedding)
            ->with('status', 'Tamu '.$guest->display_name.' ditambahkan.');
    }

    public function edit(Wedding $wedding, Guest $guest): View
    {
        $this->authorize('update', $wedding);

        return view('guests.edit', [
            'wedding' => $wedding,
            'guest' => $guest,
            'groups' => $wedding->guestGroups()->orderBy('name')->get(),
        ]);
    }

    public function update(GuestRequest $request, Wedding $wedding, Guest $guest, UpdateGuest $updateGuest): RedirectResponse
    {
        $updateGuest->handle($guest, $request->user(), $request->guestData());

        return redirect()
            ->route('weddings.guests.index', $wedding)
            ->with('status', 'Data tamu diperbarui.');
    }

    public function destroy(Request $request, Wedding $wedding, Guest $guest, DeleteGuest $deleteGuest): RedirectResponse
    {
        $this->authorize('update', $wedding);

        $deleteGuest->handle($guest, $request->user());

        return redirect()
            ->route('weddings.guests.index', $wedding)
            ->with('status', 'Tamu dihapus beserta RSVP dan tautan personalnya.');
    }

    /**
     * CSV export never includes personal links: only the contact columns the
     * owner typed, sanitised against spreadsheet formula injection.
     */
    public function export(Wedding $wedding): StreamedResponse
    {
        $this->authorize('view', $wedding);

        $filename = 'tamu-'.$wedding->slug.'-'.now()->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($wedding): void {
            $out = fopen('php://output', 'wb');

            if ($out === false) {
                return;
            }

            // UTF-8 BOM keeps accented names intact in spreadsheet apps.
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv(
                $out,
                ['name', 'group', 'phone', 'email', 'allowed_party_size', 'rsvp_status', 'rsvp_party_size', 'message', 'link_status'],
                ',',
                '"',
                '',
            );

            $wedding->guests()
                ->with(['group', 'rsvp', 'activeInvitationLink', 'latestInvitationLink'])
                ->orderBy('display_name')
                ->chunk(200, function ($guests) use ($out): void {
                    foreach ($guests as $guest) {
                        fputcsv($out, [
                            CsvSanitizer::cell($guest->display_name),
                            CsvSanitizer::cell($guest->group?->name),
                            CsvSanitizer::cell($guest->contact_phone),
                            CsvSanitizer::cell($guest->contact_email),
                            (string) $guest->allowed_party_size,
                            $guest->rsvp?->status->value ?? '',
                            $guest->rsvp === null ? '' : (string) $guest->rsvp->party_size,
                            CsvSanitizer::cell($guest->rsvp?->message),
                            $this->linkState($guest),
                        ], ',', '"', '');
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function linkState(Guest $guest): string
    {
        if ($guest->activeInvitationLink !== null) {
            return 'active';
        }

        $latest = $guest->latestInvitationLink;

        return match (true) {
            $latest === null => 'none',
            $latest->isRevoked() => 'revoked',
            default => 'expired',
        };
    }
}
