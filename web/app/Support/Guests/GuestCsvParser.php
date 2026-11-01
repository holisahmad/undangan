<?php

declare(strict_types=1);

namespace App\Support\Guests;

/**
 * Parses a guest CSV file into validated rows.
 *
 * The parser is deliberately forgiving about column order and naming
 * (Indonesian or English headers) and strict about values: every problem is
 * reported with its row number so the owner can fix the file in one pass
 * (docs/ROADMAP.md, fase 4 exit criteria).
 */
final class GuestCsvParser
{
    /**
     * Canonical column => accepted header aliases.
     *
     * @var array<string, list<string>>
     */
    private const HEADER_ALIASES = [
        'display_name' => ['name', 'nama', 'display_name', 'nama_tamu', 'nama_lengkap', 'guest_name'],
        'group' => ['group', 'grup', 'kelompok', 'group_name', 'nama_grup'],
        'phone' => ['phone', 'telepon', 'telp', 'hp', 'no_hp', 'nomor_hp', 'contact_phone', 'whatsapp', 'no_whatsapp', 'wa'],
        'email' => ['email', 'e_mail', 'surel'],
        'allowed_party_size' => ['allowed_party_size', 'party_size', 'kuota', 'jumlah_tamu', 'jumlah_orang', 'pax', 'kapasitas'],
    ];

    public function __construct(
        private readonly int $maxRows,
        private readonly int $maxPartySize,
    ) {}

    /**
     * @return array{
     *     rows: list<array{display_name: string, group: string|null, phone: string|null, email: string|null, allowed_party_size: int}>,
     *     errors: list<string>
     * }
     */
    public function parse(string $path): array
    {
        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            return ['rows' => [], 'errors' => ['Berkas CSV tidak dapat dibaca.']];
        }

        try {
            return $this->read($handle);
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  resource  $handle
     * @return array{
     *     rows: list<array{display_name: string, group: string|null, phone: string|null, email: string|null, allowed_party_size: int}>,
     *     errors: list<string>
     * }
     */
    private function read($handle): array
    {
        // Excel in an Indonesian locale writes semicolons, and "Unicode Text"
        // saves tabs. Detect the separator from the header line so a file that
        // looks correct in Excel is not rejected with a missing-column error.
        $firstLine = fgets($handle);
        $delimiter = is_string($firstLine) ? $this->detectDelimiter($firstLine) : ',';

        rewind($handle);

        $header = fgetcsv($handle, 0, $delimiter, '"', '');

        if ($header === false || $header === [null]) {
            return ['rows' => [], 'errors' => ['Berkas kosong: baris header tidak ditemukan.']];
        }

        $header[0] = $this->stripBom((string) $header[0]);

        $map = $this->mapHeader($header);

        if (! isset($map['display_name'])) {
            $detected = array_filter(array_map(
                static fn (mixed $cell): string => trim((string) $cell),
                $header,
            ));

            $preview = mb_substr(implode(', ', $detected), 0, 160);

            return ['rows' => [], 'errors' => [
                "Kolom 'name' tidak ditemukan pada baris header. Terbaca: \"{$preview}\". "
                .'Gunakan kolom: name (wajib), group, phone, email, allowed_party_size. '
                .'Pemisah kolom yang didukung: koma (,), titik koma (;), atau tab. '
                .'Simpan berkas sebagai CSV UTF-8 agar huruf dan tanda baca tidak rusak.',
            ]];
        }

        $rows = [];
        $errors = [];
        $seen = [];
        $line = 1;

        while (($cells = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
            $line++;

            if ($this->isBlankRow($cells)) {
                continue;
            }

            if (count($rows) >= $this->maxRows) {
                $errors[] = "Impor dibatasi {$this->maxRows} baris per berkas. Pecah berkas menjadi beberapa bagian.";

                break;
            }

            $row = $this->validateRow($cells, $map, $line, $seen, $errors);

            if ($row !== null) {
                $rows[] = $row;
            }
        }

        if ($rows === [] && $errors === []) {
            $errors[] = 'Tidak ada baris tamu yang bisa diimpor.';
        }

        return ['rows' => $rows, 'errors' => $errors];
    }

    /**
     * @param  list<string|null>  $header
     * @return array<string, int>
     */
    private function mapHeader(array $header): array
    {
        $map = [];

        foreach ($header as $index => $cell) {
            $clean = $this->stripBom(trim((string) $cell));
            $normalised = preg_replace('/[^a-z0-9]+/', '_', strtolower($clean)) ?? '';
            $normalised = trim($normalised, '_');

            foreach (self::HEADER_ALIASES as $canonical => $aliases) {
                if (! isset($map[$canonical]) && in_array($normalised, $aliases, true)) {
                    $map[$canonical] = $index;
                }
            }
        }

        return $map;
    }

    /**
     * Picks the separator with the most occurrences on the header line.
     * Falls back to a comma so the original error message still explains
     * which columns are expected.
     */
    private function detectDelimiter(string $line): string
    {
        $candidates = [
            ',' => substr_count($line, ','),
            ';' => substr_count($line, ';'),
            "\t" => substr_count($line, "\t"),
        ];

        arsort($candidates);

        $delimiter = (string) array_key_first($candidates);

        return ($candidates[$delimiter] ?? 0) > 0 ? $delimiter : ',';
    }

    /**
     * @param  list<string|null>  $cells
     * @param  array<string, int>  $map
     * @param  array<string, int>  $seen
     * @param  list<string>  $errors
     * @return array{display_name: string, group: string|null, phone: string|null, email: string|null, allowed_party_size: int}|null
     */
    private function validateRow(array $cells, array $map, int $line, array &$seen, array &$errors): ?array
    {
        $value = static function (string $key) use ($cells, $map): ?string {
            if (! isset($map[$key])) {
                return null;
            }

            return trim((string) ($cells[$map[$key]] ?? ''));
        };

        $name = preg_replace('/\s+/u', ' ', (string) $value('display_name')) ?? '';
        $group = $value('group');
        $phone = $value('phone');
        $email = $value('email');
        $party = $value('allowed_party_size');

        $before = count($errors);

        if ($name === '') {
            $errors[] = "Baris {$line}: nama tamu wajib diisi.";
        } elseif (mb_strlen($name) > 120) {
            $errors[] = "Baris {$line}: nama tamu maksimal 120 karakter.";
        }

        if ($group !== null && mb_strlen($group) > 60) {
            $errors[] = "Baris {$line}: nama grup maksimal 60 karakter.";
        }

        if ($phone !== null && mb_strlen($phone) > 30) {
            $errors[] = "Baris {$line}: nomor telepon maksimal 30 karakter.";
        }

        if ($email !== null && $email !== '') {
            if (mb_strlen($email) > 190 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                $errors[] = "Baris {$line}: email \"{$email}\" tidak valid.";
            }
        }

        $allowedPartySize = 1;

        if ($party !== null && $party !== '') {
            if (! ctype_digit($party)) {
                $errors[] = "Baris {$line}: allowed_party_size harus bilangan bulat.";
            } else {
                $allowedPartySize = (int) $party;

                if ($allowedPartySize < 1 || $allowedPartySize > $this->maxPartySize) {
                    $errors[] = "Baris {$line}: allowed_party_size harus antara 1 dan {$this->maxPartySize}.";
                }
            }
        }

        if ($name !== '') {
            $key = mb_strtolower($name).'|'.preg_replace('/\D+/', '', (string) $phone);

            if (isset($seen[$key])) {
                $errors[] = "Baris {$line}: \"{$name}\" duplikat dengan baris {$seen[$key]}.";
            } else {
                $seen[$key] = $line;
            }
        }

        if (count($errors) > $before) {
            return null;
        }

        return [
            'display_name' => $name,
            'group' => $group === '' ? null : $group,
            'phone' => $phone === '' ? null : $phone,
            'email' => $email === '' ? null : $email,
            'allowed_party_size' => $allowedPartySize,
        ];
    }

    /**
     * @param  list<string|null>  $cells
     */
    private function isBlankRow(array $cells): bool
    {
        foreach ($cells as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    private function stripBom(string $value): string
    {
        return str_starts_with($value, "\xEF\xBB\xBF") ? substr($value, 3) : $value;
    }
}
