<?php

declare(strict_types=1);

namespace App\Support\Media;

use GdImage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Validates image uploads and produces preview/thumbnail renders with GD.
 *
 * Re-encoding through GD strips EXIF metadata, which is the policy chosen in
 * docs/MEDIA_STORAGE.md section 3: guest photos often carry GPS data that the
 * platform does not need. Only JPEG, PNG and WebP are accepted.
 */
final class ImageProcessor
{
    private const SUPPORTED = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * @return array{mime: string, extension: string, width: int, height: int, checksum: string}
     *
     * @throws ValidationException
     */
    public function inspect(string $path): array
    {
        $info = @getimagesize($path);

        if ($info === false) {
            throw $this->invalid('Berkas yang diunggah bukan gambar yang dapat dibaca.');
        }

        $mime = (string) ($info['mime'] ?? '');
        $width = (int) ($info[0] ?? 0);
        $height = (int) ($info[1] ?? 0);

        if (! isset(self::SUPPORTED[$mime])) {
            throw $this->invalid('Format harus JPG, PNG, atau WebP.');
        }

        if ($width < 1 || $height < 1) {
            throw $this->invalid('Dimensi gambar tidak valid.');
        }

        $maxPixels = (int) config('platform.media.max_pixels', 24_000_000);

        if ($width * $height > $maxPixels) {
            throw $this->invalid('Resolusi gambar terlalu besar. Perkecil dulu sebelum mengunggah.');
        }

        $checksum = hash_file('sha256', $path);

        if ($checksum === false) {
            throw $this->invalid('Berkas tidak dapat dibaca ulang untuk pemeriksaan.');
        }

        return [
            'mime' => $mime,
            'extension' => self::SUPPORTED[$mime],
            'width' => $width,
            'height' => $height,
            'checksum' => $checksum,
        ];
    }

    /**
     * Renders are best effort: a PHP build without a JPEG/WebP codec (some
     * shared-hosting builds ship PNG only) must still accept uploads. When no
     * encoder exists the caller stores the original and leaves the preview
     * columns empty; downloads then fall back to the original.
     *
     * @return array{preview: array{bytes: string, width: int, height: int, extension: string, mime: string}, thumbnail: array{bytes: string, width: int, height: int, extension: string, mime: string}}|null
     *
     * @throws ValidationException
     */
    public function derivatives(string $path): ?array
    {
        $encoder = $this->encoder();

        if ($encoder === null) {
            Log::notice('GD build has no image encoder; media previews are disabled.');

            return null;
        }

        $previewWidth = (int) config('platform.media.preview_max_width', 1280);
        $thumbnailWidth = (int) config('platform.media.thumbnail_max_width', 400);

        try {
            return [
                'preview' => $this->render($path, $previewWidth, $encoder),
                'thumbnail' => $this->render($path, $thumbnailWidth, $encoder),
            ];
        } catch (RuntimeException) {
            Log::notice('GD could not decode this image; storing the original only.');

            return null;
        }
    }

    /**
     * @return array{bytes: string, width: int, height: int, extension: string, mime: string}
     */
    private function render(string $path, int $maxWidth, string $encoder): array
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw $this->invalid('Berkas gambar tidak dapat dibaca.');
        }

        $source = @imagecreatefromstring($contents);

        if ($source === false) {
            throw new RuntimeException('Berkas gambar tidak dapat didekode oleh GD.');
        }

        $source = $this->flatten($source);
        $width = imagesx($source);
        $height = imagesy($source);

        if ($width > $maxWidth) {
            $scaled = imagescale($source, $maxWidth, -1, IMG_BILINEAR_FIXED);

            if ($scaled === false) {
                imagedestroy($source);

                throw new RuntimeException('Gagal memperkecil gambar.');
            }

            imagedestroy($source);
            $source = $scaled;
            $width = imagesx($source);
            $height = imagesy($source);
        }

        ob_start();

        if ($encoder === 'imagejpeg') {
            imagejpeg($source, null, 82);
        } else {
            imagepng($source, null, 6);
        }

        $bytes = (string) ob_get_clean();
        imagedestroy($source);

        if ($bytes === '') {
            throw new RuntimeException('Gagal menyandikan gambar.');
        }

        return [
            'bytes' => $bytes,
            'width' => $width,
            'height' => $height,
            'extension' => $encoder === 'imagejpeg' ? 'jpg' : 'png',
            'mime' => $encoder === 'imagejpeg' ? 'image/jpeg' : 'image/png',
        ];
    }

    private function encoder(): ?string
    {
        if (function_exists('imagejpeg')) {
            return 'imagejpeg';
        }

        if (function_exists('imagepng')) {
            return 'imagepng';
        }

        return null;
    }

    /**
     * Writes transparent pixels onto white so the JPEG render never turns
     * transparent areas black.
     */
    private function flatten(GdImage $image): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $canvas = imagecreatetruecolor($width, $height);

        if ($canvas === false) {
            imagedestroy($image);

            throw new RuntimeException('Gagal menyiapkan kanvas gambar.');
        }

        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $width, $height, (int) $white);
        imagecopy($canvas, $image, 0, 0, 0, 0, $width, $height);
        imagedestroy($image);

        return $canvas;
    }

    private function invalid(string $message): ValidationException
    {
        return ValidationException::withMessages(['photos' => $message]);
    }
}
