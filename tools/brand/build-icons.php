<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Brand asset generator (Wedly)
|--------------------------------------------------------------------------
|
| Rebuilds the favicon/app-icon set and the Open Graph image from the brand
| artwork that lives in the repository root:
|
|   Glossy Heart, Diamond, and Envelope Emblem.png   -> the mark
|   Wedly Luxury Wedding Invitation Logo.png         -> the full lockup
|
| Run from anywhere:  php tools/brand/build-icons.php
|
| The art ships on an opaque near-white plate (254,254,254), so that plate is
| knocked out to transparency before anything is composed on the brand's warm
| ivory tile (docs/DESIGN_SYSTEM.md, "Identitas merek").
|
*/

const INK_THRESHOLD = 245;
const IVORY = [255, 249, 244];
const BRAND_BURGUNDY = '#A6294B';

$root = dirname(__DIR__, 2);
$out = $root.'/web/public';

const MARK_SOURCE = 'Glossy Heart, Diamond, and Envelope Emblem.png';
const LOGO_SOURCE = 'Wedly Luxury Wedding Invitation Logo.png';

function load(string $file): GdImage
{
    $im = imagecreatefrompng($file);
    if (! $im instanceof GdImage) {
        throw new RuntimeException('Cannot read '.$file);
    }

    imagesavealpha($im, true);

    return $im;
}

/** A pixel belongs to the artwork when it is neither transparent nor white. */
function isInk(int $rgba): bool
{
    if ((($rgba >> 24) & 0x7F) > 110) {
        return false;
    }

    return min(($rgba >> 16) & 0xFF, ($rgba >> 8) & 0xFF, $rgba & 0xFF) < INK_THRESHOLD;
}

/**
 * Turn the near-white plate into transparency, fading smoothly so the edges
 * keep their anti-aliasing. The saturation test (the smallest channel) leaves
 * coloured highlights alone: only pixels whose darkest channel is almost white
 * are candidates.
 */
function knockOutWhite(GdImage $im): GdImage
{
    $width = imagesx($im);
    $height = imagesy($im);
    $out = blank($width, $height);

    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            $pixel = imagecolorat($im, $x, $y);
            $alpha = ($pixel >> 24) & 0x7F;
            $darkest = min(($pixel >> 16) & 0xFF, ($pixel >> 8) & 0xFF, $pixel & 0xFF);

            // 235 -> untouched, 255 -> fully transparent.
            $whiteness = max(0, min(1, ($darkest - 235) / 20));
            $alpha = max($alpha, (int) round($whiteness * 127));

            imagesetpixel($out, $x, $y, imagecolorallocatealpha(
                $out,
                ($pixel >> 16) & 0xFF,
                ($pixel >> 8) & 0xFF,
                $pixel & 0xFF,
                min(127, $alpha),
            ));
        }
    }

    return $out;
}

function blank(int $width, int $height, array $rgb = [255, 255, 255], bool $transparent = true): GdImage
{
    $im = imagecreatetruecolor($width, $height);
    imagealphablending($im, false);
    imagesavealpha($im, true);
    imagefill($im, 0, 0, $transparent
        ? imagecolorallocatealpha($im, 255, 255, 255, 127)
        : imagecolorallocate($im, ...$rgb));
    imagealphablending($im, true);

    return $im;
}

function crop(GdImage $im, int $left, int $top, int $width, int $height): GdImage
{
    $out = blank($width, $height);

    // Clamp to the source: a centred square taller than the art would read
    // outside the bitmap, and GD paints that region opaque black.
    $sourceX = max(0, $left);
    $sourceY = max(0, $top);
    $sourceWidth = min($width - ($sourceX - $left), imagesx($im) - $sourceX);
    $sourceHeight = min($height - ($sourceY - $top), imagesy($im) - $sourceY);

    if ($sourceWidth <= 0 || $sourceHeight <= 0) {
        return $out;
    }

    imagecopy(
        $out,
        $im,
        $sourceX - $left,
        $sourceY - $top,
        $sourceX,
        $sourceY,
        $sourceWidth,
        $sourceHeight,
    );

    return $out;
}

/** Trim transparent and white margins. */
function trimArt(GdImage $im): GdImage
{
    $w = imagesx($im);
    $h = imagesy($im);
    $left = $w;
    $right = 0;
    $top = $h;
    $bottom = 0;

    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            if (isInk(imagecolorat($im, $x, $y))) {
                $left = min($left, $x);
                $right = max($right, $x);
                $top = min($top, $y);
                $bottom = max($bottom, $y);
            }
        }
    }

    return $left > $right ? $im : crop($im, $left, $top, $right - $left + 1, $bottom - $top + 1);
}

/**
 * Square crop around the middle, wide enough to keep the whole heart. The
 * ribbon wings still run off the edges, exactly like the app icon on the brand
 * board, but the tile does not turn into a letterboxed strip.
 */
function square(GdImage $im, float $heightFactor = 1.2): GdImage
{
    $w = imagesx($im);
    $h = imagesy($im);
    $side = min($w, (int) round($h * $heightFactor));

    return crop($im, intdiv($w - $side, 2), intdiv($h - $side, 2), $side, $side);
}

function scale(GdImage $src, int $width, int $height): GdImage
{
    $dst = blank($width, $height);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $width, $height, imagesx($src), imagesy($src));

    return $dst;
}

/** Halve repeatedly before the final resample; a single 1500 -> 16 step blurs. */
function scaleToHeight(GdImage $src, int $height): GdImage
{
    $current = $src;

    while (imagesy($current) > $height * 2) {
        $current = scale($current, max(1, (int) round(imagesx($current) / 2)), max(1, (int) round(imagesy($current) / 2)));
    }

    $ratio = $height / imagesy($current);

    return scale($current, max(1, (int) round(imagesx($current) * $ratio)), $height);
}

function scaleToWidth(GdImage $src, int $width): GdImage
{
    $current = $src;

    while (imagesx($current) > $width * 2) {
        $current = scale($current, max(1, (int) round(imagesx($current) / 2)), max(1, (int) round(imagesy($current) / 2)));
    }

    $ratio = $width / imagesx($current);

    return scale($current, $width, max(1, (int) round(imagesy($current) * $ratio)));
}

/**
 * Composite an RGBA source onto an opaque canvas, blending by alpha.
 *
 * imagecopy() writes the stored RGB of transparent pixels (the artwork keeps
 * 254,254,254 there) which leaves a visible white plate when the canvas is
 * ivory. This blends the source onto the destination instead.
 */
function stamp(GdImage $dst, GdImage $src, int $left, int $top): void
{
    $width = imagesx($src);
    $height = imagesy($src);

    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            $pixel = imagecolorat($src, $x, $y);
            $alpha = ($pixel >> 24) & 0x7F;

            if ($alpha > 110) {
                continue;
            }

            $opacity = (127 - $alpha) / 127;
            $target = imagecolorat($dst, $left + $x, $top + $y);
            $mix = static fn (int $source, int $destination): int => (int) round($source * $opacity + $destination * (1 - $opacity));

            imagesetpixel($dst, $left + $x, $top + $y, imagecolorallocate(
                $dst,
                $mix(($pixel >> 16) & 0xFF, ($target >> 16) & 0xFF),
                $mix(($pixel >> 8) & 0xFF, ($target >> 8) & 0xFF),
                $mix($pixel & 0xFF, $target & 0xFF),
            ));
        }
    }
}

/** Ivory tile with the mark inset, used for every icon size. */
function tile(GdImage $mark, int $size): GdImage
{
    $canvas = blank($size, $size, IVORY, false);

    $scaled = scaleToHeight($mark, (int) round($size * 0.8));
    stamp($canvas, $scaled, intdiv($size - imagesx($scaled), 2), intdiv($size - imagesy($scaled), 2));

    return $canvas;
}

function png(GdImage $im, string $path): void
{
    imagepng($im, $path, 9);
    printf("  %-42s %s\n", basename($path), number_format(filesize($path) / 1024, 1).' kB');
}

function pngBytes(GdImage $im): string
{
    ob_start();
    imagepng($im, null, 9);

    return (string) ob_get_clean();
}

/** Multi-resolution .ico built from PNG frames (supported by every modern browser). */
function ico(array $frames, string $path): void
{
    $header = pack('vvv', 0, 1, count($frames));
    $offset = 6 + (count($frames) * 16);
    $entries = '';
    $blobs = '';

    foreach ($frames as [$size, $blob]) {
        $length = strlen($blob);
        $entries .= pack('CCCCvvVV', $size, $size, 0, 0, 1, 32, $length, $offset);
        $blobs .= $blob;
        $offset += $length;
    }

    file_put_contents($path, $header.$entries.$blobs);
    printf("  %-42s %s\n", basename($path), number_format(filesize($path) / 1024, 1).' kB');
}

@mkdir($out.'/brand', 0777, true);

echo "Mark\n";
$emblem = trimArt(knockOutWhite(load($root.'/'.MARK_SOURCE)));
png(scaleToHeight($emblem, 512), $out.'/brand/wedly-emblem.png');

$mark = square($emblem);
png(scaleToHeight($mark, 512), $out.'/brand/wedly-mark.png');
png(scaleToHeight($mark, 96), $out.'/brand/wedly-mark-96.png');

echo "Logotype\n";
$logo = trimArt(knockOutWhite(load($root.'/'.LOGO_SOURCE)));
png(scaleToWidth($logo, 1000), $out.'/brand/wedly-logo.png');

echo "Icons\n";
foreach ([32, 180, 192, 512] as $size) {
    png(tile($mark, $size), match ($size) {
        180 => $out.'/apple-touch-icon.png',
        192 => $out.'/icon-192.png',
        512 => $out.'/icon-512.png',
        default => $out.'/favicon-'.$size.'.png',
    });
}

echo "favicon.ico\n";
ico(
    array_map(static fn (int $size): array => [$size, pngBytes(tile($mark, $size))], [16, 32, 48]),
    $out.'/favicon.ico',
);

echo "Open Graph image (1200x630)\n";
$og = blank(1200, 630, IVORY, false);
$logoOg = scaleToHeight($logo, 520);
stamp($og, $logoOg, intdiv(1200 - imagesx($logoOg), 2), intdiv(630 - imagesy($logoOg), 2));
png($og, $out.'/og-wedly.png');

printf("\nDone. theme_color %s, background #FFF9F4\n", BRAND_BURGUNDY);
