<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Guests\ImportGuests;
use App\Http\Requests\ImportGuestsRequest;
use App\Models\Wedding;
use App\Support\Guests\GuestCsvParser;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class GuestImportController extends Controller
{
    /**
     * Import is all-or-nothing: when the parser finds problems nothing is
     * written, and the first errors are returned with their row numbers.
     */
    public function store(
        ImportGuestsRequest $request,
        Wedding $wedding,
        GuestCsvParser $parser,
        ImportGuests $importGuests,
    ): RedirectResponse {
        $file = $request->file('file');
        $path = $file?->getRealPath();

        if ($file === null || $path === false) {
            return back()->withErrors(['file' => 'Berkas tidak dapat dibaca.']);
        }

        $result = $parser->parse($path);

        if ($result['errors'] !== []) {
            $shown = array_slice($result['errors'], 0, 20);

            if (count($result['errors']) > 20) {
                $shown[] = 'Masih ada '.(count($result['errors']) - 20).' masalah lain. Perbaiki lalu unggah ulang.';
            }

            return back()->withErrors([
                'file' => array_merge(['Tidak ada tamu yang diimpor. Perbaiki baris berikut:'], $shown),
            ]);
        }

        $count = $importGuests->handle($wedding, $result['rows'], $request->user());

        return redirect()
            ->route('weddings.guests.index', $wedding)
            ->with('status', $count.' tamu berhasil diimpor.');
    }

    /**
     * Downloadable starter file: the exact headers the parser accepts plus two
     * example rows, so owners never have to guess the column names.
     */
    public function template(Wedding $wedding): StreamedResponse
    {
        $this->authorize('view', $wedding);

        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'wb');

            if ($out === false) {
                return;
            }

            // UTF-8 BOM keeps Excel from mangling accented names on open.
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['name', 'group', 'phone', 'email', 'allowed_party_size'], ',', '"', '');
            fputcsv($out, ['Budi Santoso', 'Keluarga', '081200000001', 'budi@example.test', '2'], ',', '"', '');
            fputcsv($out, ['Siti Aminah', 'Teman Kantor', '', '', '1'], ',', '"', '');

            fclose($out);
        }, 'template-impor-tamu.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
