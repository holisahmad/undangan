<?php

declare(strict_types=1);

namespace App\Support\Invitations;

use InvalidArgumentException;

/**
 * Minimal QR encoder used by invitation covers that carry a scannable link.
 *
 * Scope is deliberately small: 8-bit byte mode, error correction level M and
 * versions 1-10 (up to 213 bytes), which covers every URL this application
 * prints. A dependency-free encoder keeps the templates free of third-party
 * code and lets the output be rendered as inline SVG, so no image request
 * leaves the guest's browser (docs/SECURITY.md, CMS policy).
 */
final class QrCode
{
    /**
     * Error correction level M. Format bits are 00, the two highest payload
     * capacities per module size still recover from a scuffed print.
     */
    private const EC_LEVEL_BITS = 0;

    private const MAX_VERSION = 10;

    private const QUIET_ZONE = 4;

    /**
     * @var array{0: array<int, int>, 1: array<int, int>}|null
     */
    private static ?array $gf = null;

    /**
     * Per version (1-10), level M: EC codewords per block and the data blocks
     * as [amount, data codewords] pairs.
     *
     * @var array<int, array{ec: int, groups: list<array{0: int, 1: int}>}>
     */
    private const BLOCKS = [
        1 => ['ec' => 10, 'groups' => [[1, 16]]],
        2 => ['ec' => 16, 'groups' => [[1, 28]]],
        3 => ['ec' => 26, 'groups' => [[1, 44]]],
        4 => ['ec' => 18, 'groups' => [[2, 32]]],
        5 => ['ec' => 24, 'groups' => [[2, 43]]],
        6 => ['ec' => 16, 'groups' => [[4, 27]]],
        7 => ['ec' => 18, 'groups' => [[4, 31]]],
        8 => ['ec' => 22, 'groups' => [[2, 38], [2, 39]]],
        9 => ['ec' => 22, 'groups' => [[3, 36], [2, 37]]],
        10 => ['ec' => 26, 'groups' => [[4, 43], [1, 44]]],
    ];

    /**
     * @var array<int, list<int>>
     */
    private const ALIGNMENT_CENTERS = [
        1 => [],
        2 => [6, 18],
        3 => [6, 22],
        4 => [6, 26],
        5 => [6, 30],
        6 => [6, 34],
        7 => [6, 22, 38],
        8 => [6, 24, 42],
        9 => [6, 26, 46],
        10 => [6, 28, 50],
    ];

    /**
     * Renders the code as a self-contained SVG document.
     *
     * The light modules are left transparent so the cover colour shows through;
     * a caller that needs a printed card can pass an explicit background.
     */
    public static function svg(string $text, string $dark = '#0b1f16', ?string $light = null, string $label = 'Tautan undangan'): string
    {
        $matrix = self::matrix($text);
        $size = count($matrix);
        $path = '';

        foreach ($matrix as $row => $columns) {
            foreach ($columns as $column => $isDark) {
                if ($isDark) {
                    $path .= 'M'.$column.' '.$row.'h1v1h-1z';
                }
            }
        }

        $background = $light === null
            ? ''
            : '<rect width="'.$size.'" height="'.$size.'" fill="'.e($light).'"/>';

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$size.' '.$size.'" '
            .'role="img" aria-label="'.e($label).'" shape-rendering="crispEdges" '
            .'width="100%" height="100%">'
            .$background
            .'<path d="'.$path.'" fill="'.e($dark).'"/>'
            .'</svg>';
    }

    /**
     * Returns the module grid including the four-module quiet zone.
     *
     * @return list<list<bool>>
     */
    public static function matrix(string $text): array
    {
        $bytes = self::bytes($text);
        $version = self::versionFor(count($bytes));
        $codewords = self::codewords($bytes, $version);
        $size = 17 + ($version * 4);

        [$modules, $reserved] = self::functionPatterns($size, $version);

        $best = null;
        $bestPenalty = PHP_INT_MAX;
        $bestFormat = 0;

        for ($mask = 0; $mask < 8; $mask++) {
            $candidate = self::placeData($modules, $reserved, $codewords, $size, $mask);
            $format = self::formatBits($mask);
            self::writeFormat($candidate, $size, $format);

            if ($version >= 7) {
                self::writeVersion($candidate, $size, $version);
            }

            $penalty = self::penalty($candidate, $size);

            if ($penalty < $bestPenalty) {
                $bestPenalty = $penalty;
                $best = $candidate;
                $bestFormat = $format;
            }
        }

        /** @var list<list<bool>> $winner */
        $winner = $best;
        self::writeFormat($winner, $size, $bestFormat);

        if ($version >= 7) {
            self::writeVersion($winner, $size, $version);
        }

        return self::withQuietZone($winner);
    }

    /**
     * @return list<int>
     */
    private static function bytes(string $text): array
    {
        if ($text === '') {
            throw new InvalidArgumentException('A QR code needs a non-empty payload.');
        }

        /** @var list<int> $bytes */
        $bytes = array_values(unpack('C*', $text) ?: []);

        return $bytes;
    }

    private static function versionFor(int $byteLength): int
    {
        for ($version = 1; $version <= self::MAX_VERSION; $version++) {
            $countBits = $version < 10 ? 8 : 16;

            if (($byteLength * 8) + 4 + $countBits <= self::dataCodewords($version) * 8) {
                return $version;
            }
        }

        throw new InvalidArgumentException('Payload is too long for this QR encoder.');
    }

    private static function dataCodewords(int $version): int
    {
        $total = 0;

        foreach (self::BLOCKS[$version]['groups'] as [$amount, $length]) {
            $total += $amount * $length;
        }

        return $total;
    }

    /**
     * @param  list<int>  $bytes
     * @return list<int>
     */
    private static function codewords(array $bytes, int $version): array
    {
        $countBits = $version < 10 ? 8 : 16;
        $capacity = self::dataCodewords($version) * 8;

        $bits = [0, 1, 0, 0]; // Byte mode.

        for ($i = $countBits - 1; $i >= 0; $i--) {
            $bits[] = (count($bytes) >> $i) & 1;
        }

        foreach ($bytes as $byte) {
            for ($i = 7; $i >= 0; $i--) {
                $bits[] = ($byte >> $i) & 1;
            }
        }

        $bits = array_pad($bits, count($bits) + min(4, max(0, $capacity - count($bits))), 0);

        while (count($bits) % 8 !== 0) {
            $bits[] = 0;
        }

        /** @var list<int> $data */
        $data = array_map(
            static fn (array $chunk): int => (int) bindec(implode('', $chunk)),
            array_chunk($bits, 8),
        );

        $pads = [0xEC, 0x11];
        $padIndex = 0;

        while (count($data) < self::dataCodewords($version)) {
            $data[] = $pads[$padIndex++ % 2];
        }

        return self::interleave($data, self::BLOCKS[$version]);
    }

    /**
     * Splits the data codewords into blocks, appends their Reed-Solomon
     * codewords and interleaves both halves as the specification requires.
     *
     * @param  list<int>  $data
     * @param  array{ec: int, groups: list<array{0: int, 1: int}>}  $definition
     * @return list<int>
     */
    private static function interleave(array $data, array $definition): array
    {
        $blocks = [];
        $offset = 0;

        foreach ($definition['groups'] as [$amount, $length]) {
            for ($block = 0; $block < $amount; $block++) {
                $chunk = array_slice($data, $offset, $length);
                $offset += $length;
                $blocks[] = [
                    'data' => $chunk,
                    'ec' => self::remainder($chunk, $definition['ec']),
                ];
            }
        }

        $result = [];
        $widest = max(array_map(static fn (array $block): int => count($block['data']), $blocks));

        for ($i = 0; $i < $widest; $i++) {
            foreach ($blocks as $block) {
                if (isset($block['data'][$i])) {
                    $result[] = $block['data'][$i];
                }
            }
        }

        for ($i = 0; $i < $definition['ec']; $i++) {
            foreach ($blocks as $block) {
                $result[] = $block['ec'][$i];
            }
        }

        return $result;
    }

    /**
     * @param  list<int>  $data
     * @return list<int>
     */
    private static function remainder(array $data, int $ecLength): array
    {
        $divisor = self::generator($ecLength);
        $message = array_merge($data, array_fill(0, $ecLength, 0));

        // Read every coefficient from the message itself: the loop rewrites
        // earlier entries, and a foreach over the array would keep the stale
        // pre-division values. Only the data part is divided; the trailing
        // slots collect the remainder.
        $positions = count($message) - $ecLength;

        for ($index = 0; $index < $positions; $index++) {
            $factor = $message[$index];

            if ($factor === 0) {
                continue;
            }

            for ($i = 0; $i < count($divisor); $i++) {
                if (! isset($message[$index + $i])) {
                    break;
                }

                $message[$index + $i] ^= self::multiply($divisor[$i], $factor);
            }
        }

        return array_slice($message, count($data));
    }

    /**
     * @return list<int>
     */
    private static function generator(int $ecLength): array
    {
        $polynomial = [1];
        [$exp] = self::gf();

        for ($i = 0; $i < $ecLength; $i++) {
            $next = array_fill(0, count($polynomial) + 1, 0);

            // Coefficients are stored highest degree first, so multiplying by
            // (x - alpha^i) shifts one step right and adds alpha^i in place.
            foreach ($polynomial as $index => $coefficient) {
                $next[$index] ^= $coefficient;
                $next[$index + 1] ^= self::multiply($coefficient, $exp[$i]);
            }

            $polynomial = $next;
        }

        return $polynomial;
    }

    private static function multiply(int $left, int $right): int
    {
        if ($left === 0 || $right === 0) {
            return 0;
        }

        [$exp, $log] = self::gf();

        return $exp[($log[$left] + $log[$right]) % 255];
    }

    /**
     * @return array{0: list<list<bool>>, 1: list<list<bool>>}
     */
    private static function functionPatterns(int $size, int $version): array
    {
        $modules = array_fill(0, $size, array_fill(0, $size, false));
        $reserved = array_fill(0, $size, array_fill(0, $size, false));

        self::finder($modules, $reserved, 0, 0);
        self::finder($modules, $reserved, $size - 7, 0);
        self::finder($modules, $reserved, 0, $size - 7);

        for ($i = 8; $i < $size - 8; $i++) {
            $dark = $i % 2 === 0;
            $modules[6][$i] = $dark;
            $modules[$i][6] = $dark;
            $reserved[6][$i] = true;
            $reserved[$i][6] = true;
        }

        $centers = self::ALIGNMENT_CENTERS[$version];

        foreach ($centers as $row) {
            foreach ($centers as $column) {
                if (($row === 6 && $column === 6)
                    || ($row === 6 && $column === $size - 7)
                    || ($row === $size - 7 && $column === 6)) {
                    continue;
                }

                self::alignment($modules, $reserved, $row, $column);
            }
        }

        // The dark module is always set, and the two format areas plus the
        // version areas have to be reserved before the data lands.
        $modules[$size - 8][8] = true;
        $reserved[$size - 8][8] = true;

        for ($i = 0; $i < 9; $i++) {
            $reserved[8][$i] = true;
            $reserved[$i][8] = true;
        }

        for ($i = 0; $i < 8; $i++) {
            $reserved[8][$size - 1 - $i] = true;
            $reserved[$size - 1 - $i][8] = true;
        }

        if ($version >= 7) {
            for ($i = 0; $i < 6; $i++) {
                for ($j = 0; $j < 3; $j++) {
                    $reserved[$i][$size - 11 + $j] = true;
                    $reserved[$size - 11 + $j][$i] = true;
                }
            }
        }

        return [$modules, $reserved];
    }

    /**
     * @param  list<list<bool>>  $modules
     * @param  list<list<bool>>  $reserved
     */
    private static function finder(array &$modules, array &$reserved, int $row, int $column): void
    {
        for ($r = -1; $r <= 7; $r++) {
            for ($c = -1; $c <= 7; $c++) {
                $targetRow = $row + $r;
                $targetColumn = $column + $c;

                if ($targetRow < 0 || $targetColumn < 0 || $targetRow >= count($modules) || $targetColumn >= count($modules)) {
                    continue;
                }

                $isDark = $r >= 0 && $r <= 6 && $c >= 0 && $c <= 6
                    && ($r === 0 || $r === 6 || $c === 0 || $c === 6 || ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4));

                $modules[$targetRow][$targetColumn] = $isDark;
                $reserved[$targetRow][$targetColumn] = true;
            }
        }
    }

    /**
     * @param  list<list<bool>>  $modules
     * @param  list<list<bool>>  $reserved
     */
    private static function alignment(array &$modules, array &$reserved, int $row, int $column): void
    {
        for ($r = -2; $r <= 2; $r++) {
            for ($c = -2; $c <= 2; $c++) {
                $isDark = max(abs($r), abs($c)) !== 1;
                $modules[$row + $r][$column + $c] = $isDark;
                $reserved[$row + $r][$column + $c] = true;
            }
        }
    }

    /**
     * @param  list<list<bool>>  $modules
     * @param  list<list<bool>>  $reserved
     * @param  list<int>  $codewords
     * @return list<list<bool>>
     */
    private static function placeData(array $modules, array $reserved, array $codewords, int $size, int $mask): array
    {
        $bits = [];

        foreach ($codewords as $codeword) {
            for ($i = 7; $i >= 0; $i--) {
                $bits[] = ($codeword >> $i) & 1;
            }
        }

        $total = count($bits);
        $index = 0;
        $row = $size - 1;
        $direction = -1;

        for ($column = $size - 1; $column > 0; $column -= 2) {
            if ($column === 6) {
                $column--;
            }

            while (true) {
                for ($offset = 0; $offset < 2; $offset++) {
                    $target = $column - $offset;

                    if ($reserved[$row][$target]) {
                        continue;
                    }

                    $dark = $index < $total && $bits[$index] === 1;
                    $index++;

                    $modules[$row][$target] = self::masked($mask, $row, $target) ? ! $dark : $dark;
                }

                $row += $direction;

                if ($row < 0 || $row >= $size) {
                    $row -= $direction;
                    $direction = -$direction;
                    break;
                }
            }
        }

        return $modules;
    }

    private static function masked(int $mask, int $row, int $column): bool
    {
        return match ($mask) {
            0 => ($row + $column) % 2 === 0,
            1 => $row % 2 === 0,
            2 => $column % 3 === 0,
            3 => ($row + $column) % 3 === 0,
            4 => (intdiv($row, 2) + intdiv($column, 3)) % 2 === 0,
            5 => (($row * $column) % 2) + (($row * $column) % 3) === 0,
            6 => ((($row * $column) % 2) + (($row * $column) % 3)) % 2 === 0,
            default => ((($row * $column) % 3) + (($row + $column) % 2)) % 2 === 0,
        };
    }

    private static function formatBits(int $mask): int
    {
        $data = (self::EC_LEVEL_BITS << 3) | $mask;
        $remainder = $data << 10;
        $divisor = 0b10100110111;

        while (self::bitLength($remainder) >= self::bitLength($divisor)) {
            $remainder ^= $divisor << (self::bitLength($remainder) - self::bitLength($divisor));
        }

        return (($data << 10) | $remainder) ^ 0b101010000010010;
    }

    /**
     * @param  list<list<bool>>  $modules
     */
    private static function writeFormat(array &$modules, int $size, int $bits): void
    {
        for ($i = 0; $i < 15; $i++) {
            $dark = (($bits >> $i) & 1) === 1;

            if ($i < 6) {
                $modules[$i][8] = $dark;
            } elseif ($i < 8) {
                $modules[$i + 1][8] = $dark;
            } else {
                $modules[$size - 15 + $i][8] = $dark;
            }

            if ($i < 8) {
                $modules[8][$size - $i - 1] = $dark;
            } elseif ($i < 9) {
                $modules[8][15 - $i - 1 + 1] = $dark;
            } else {
                $modules[8][15 - $i - 1] = $dark;
            }
        }

        $modules[$size - 8][8] = true;
    }

    /**
     * @param  list<list<bool>>  $modules
     */
    private static function writeVersion(array &$modules, int $size, int $version): void
    {
        $remainder = $version << 12;
        $divisor = 0b1111100100101;

        while (self::bitLength($remainder) >= self::bitLength($divisor)) {
            $remainder ^= $divisor << (self::bitLength($remainder) - self::bitLength($divisor));
        }

        $bits = ($version << 12) | $remainder;

        for ($i = 0; $i < 18; $i++) {
            $dark = (($bits >> $i) & 1) === 1;
            $modules[intdiv($i, 3)][($i % 3) + $size - 11] = $dark;
            $modules[($i % 3) + $size - 11][intdiv($i, 3)] = $dark;
        }
    }

    private static function bitLength(int $value): int
    {
        $length = 0;

        while ($value !== 0) {
            $length++;
            $value >>= 1;
        }

        return $length;
    }

    /**
     * @param  list<list<bool>>  $modules
     */
    private static function penalty(array $modules, int $size): int
    {
        $score = 0;

        for ($row = 0; $row < $size; $row++) {
            $score += self::linePenalty(array_values($modules[$row]));
            $score += self::linePenalty(array_map(static fn (int $column): bool => $modules[$column][$row], range(0, $size - 1)));
        }

        for ($row = 0; $row < $size - 1; $row++) {
            for ($column = 0; $column < $size - 1; $column++) {
                $value = $modules[$row][$column];

                if ($value === $modules[$row][$column + 1]
                    && $value === $modules[$row + 1][$column]
                    && $value === $modules[$row + 1][$column + 1]) {
                    $score += 3;
                }
            }
        }

        $dark = 0;

        foreach ($modules as $columns) {
            foreach ($columns as $module) {
                if ($module) {
                    $dark++;
                }
            }
        }

        $total = $size * $size;
        $ratio = ($dark * 100) / $total;
        $deviation = (int) floor(abs($ratio - 50) / 5);

        return $score + ($deviation * 10);
    }

    /**
     * @param  list<bool>  $line
     */
    private static function linePenalty(array $line): int
    {
        $score = 0;
        $length = count($line);
        $run = 1;

        for ($i = 1; $i < $length; $i++) {
            if ($line[$i] === $line[$i - 1]) {
                $run++;
            } else {
                if ($run >= 5) {
                    $score += 3 + ($run - 5);
                }

                $run = 1;
            }
        }

        if ($run >= 5) {
            $score += 3 + ($run - 5);
        }

        for ($i = 0; $i <= $length - 11; $i++) {
            $window = array_slice($line, $i, 11);
            $pattern = implode('', array_map(static fn (bool $module): string => $module ? '1' : '0', $window));

            if ($pattern === '10111010000' || $pattern === '00001011101') {
                $score += 40;
            }
        }

        return $score;
    }

    /**
     * @param  list<list<bool>>  $modules
     * @return list<list<bool>>
     */
    private static function withQuietZone(array $modules): array
    {
        $size = count($modules);
        $padded = array_fill(0, $size + (self::QUIET_ZONE * 2), array_fill(0, $size + (self::QUIET_ZONE * 2), false));

        foreach ($modules as $row => $columns) {
            foreach ($columns as $column => $module) {
                $padded[$row + self::QUIET_ZONE][$column + self::QUIET_ZONE] = $module;
            }
        }

        return $padded;
    }

    /**
     * GF(256) exponent and logarithm tables over the QR polynomial 0x11D.
     *
     * @return array{0: array<int, int>, 1: array<int, int>}
     */
    private static function gf(): array
    {
        if (self::$gf !== null) {
            return self::$gf;
        }

        $exp = [];
        $log = [];
        $value = 1;

        for ($i = 0; $i < 255; $i++) {
            $exp[$i] = $value;
            $log[$value] = $i;
            $value <<= 1;

            if ($value & 0x100) {
                $value ^= 0x11D;
            }
        }

        return self::$gf = [$exp, $log];
    }
}
