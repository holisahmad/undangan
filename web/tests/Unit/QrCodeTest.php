<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Invitations\QrCode;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * The invitation cover prints a scannable link, so the encoder is pinned to
 * sizes taken from the specification and to one golden matrix that was decoded
 * with an independent reader while it was written.
 */
final class QrCodeTest extends TestCase
{
    public function test_it_picks_the_smallest_version_that_fits_the_payload(): void
    {
        // Version n is 17 + 4n modules, plus the four-module quiet zone.
        $this->assertCount(29, QrCode::matrix(str_repeat('a', 14)));
        $this->assertCount(33, QrCode::matrix(str_repeat('a', 15)));
        $this->assertCount(65, QrCode::matrix(str_repeat('a', 213)));
    }

    public function test_it_refuses_a_payload_that_no_supported_version_can_hold(): void
    {
        $this->expectException(InvalidArgumentException::class);

        QrCode::matrix(str_repeat('a', 214));
    }

    public function test_it_refuses_an_empty_payload(): void
    {
        $this->expectException(InvalidArgumentException::class);

        QrCode::matrix('');
    }

    public function test_the_grid_is_square_and_surrounded_by_a_quiet_zone(): void
    {
        $matrix = QrCode::matrix('https://wedly.test/g/abc123');
        $size = count($matrix);

        foreach ($matrix as $row) {
            $this->assertCount($size, $row);
        }

        for ($index = 0; $index < $size; $index++) {
            for ($edge = 0; $edge < 4; $edge++) {
                $this->assertFalse($matrix[$edge][$index], 'quiet zone row');
                $this->assertFalse($matrix[$size - 1 - $edge][$index], 'quiet zone row');
                $this->assertFalse($matrix[$index][$edge], 'quiet zone column');
                $this->assertFalse($matrix[$index][$size - 1 - $edge], 'quiet zone column');
            }
        }
    }

    public function test_the_finder_patterns_sit_in_three_corners(): void
    {
        $matrix = QrCode::matrix('https://wedly.test/g/abc123');
        $core = count($matrix) - 8;
        $offset = 4;

        foreach ([[0, 0], [0, $core - 7], [$core - 7, 0]] as [$row, $column]) {
            for ($r = 0; $r < 7; $r++) {
                for ($c = 0; $c < 7; $c++) {
                    $expected = $r === 0 || $r === 6 || $c === 0 || $c === 6
                        || ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4);

                    $this->assertSame($expected, $matrix[$row + $offset + $r][$column + $offset + $c]);
                }
            }
        }
    }

    public function test_the_matrix_stays_stable_for_a_known_payload(): void
    {
        $matrix = QrCode::matrix('https://wedly.test/g/abc123');
        $flat = '';

        foreach ($matrix as $row) {
            foreach ($row as $module) {
                $flat .= $module ? '1' : '0';
            }
        }

        $this->assertCount(37, $matrix);
        $this->assertSame('86679655458c420e3afb848aeb9c1c1ffcdaf3f5', sha1($flat));
    }

    public function test_the_svg_carries_no_external_reference(): void
    {
        $svg = QrCode::svg('https://wedly.test/u/ayu-bima', '#0b3327', '#fffdf6', 'Kode QR undangan');

        $this->assertStringStartsWith('<svg xmlns="http://www.w3.org/2000/svg"', $svg);
        $this->assertStringContainsString('shape-rendering="crispEdges"', $svg);
        $this->assertStringContainsString('aria-label="Kode QR undangan"', $svg);
        $this->assertStringContainsString('fill="#fffdf6"', $svg);
        $this->assertStringNotContainsString('<image', $svg);
        $this->assertStringNotContainsString('script', $svg);
    }
}
