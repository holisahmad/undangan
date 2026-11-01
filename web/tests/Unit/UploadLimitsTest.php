<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Media\UploadLimits;
use Tests\TestCase;

/**
 * The gallery promises the lower of the product config and PHP's own limits;
 * advertising 8 MB on a host capped at 2 MB is exactly how an upload ends in a
 * bare 413 (docs/MEDIA_STORAGE.md, section 10).
 */
final class UploadLimitsTest extends TestCase
{
    public function test_php_ini_shorthands_are_converted_to_kilobytes(): void
    {
        $this->assertSame(2048, UploadLimits::toKb('2M'));
        $this->assertSame(8192, UploadLimits::toKb('8M'));
        $this->assertSame(512, UploadLimits::toKb('512K'));
        $this->assertSame(1_048_576, UploadLimits::toKb('1G'));
        $this->assertSame(8192, UploadLimits::toKb('8388608'));
        $this->assertSame(1536, UploadLimits::toKb(' 1.5M '));
    }

    public function test_unlimited_and_unparseable_values_are_ignored(): void
    {
        $this->assertNull(UploadLimits::toKb('0'));
        $this->assertNull(UploadLimits::toKb('-1'));
        $this->assertNull(UploadLimits::toKb(''));
        $this->assertNull(UploadLimits::toKb('unlimited'));
    }

    public function test_php_limits_lower_the_configured_ceilings(): void
    {
        $limits = new UploadLimits(8192, 24576, 10, 2048, 8192);

        $this->assertSame(2048, $limits->fileKb());
        $this->assertSame(7680, $limits->requestKb());
        $this->assertSame(2, $limits->fileMb());
        $this->assertSame('7,5', $limits->requestMb());
        $this->assertTrue($limits->clampedByPhp());
    }

    public function test_a_generous_php_config_does_not_raise_the_product_ceiling(): void
    {
        $limits = new UploadLimits(8192, 24576, 10, 65_536, 131_072);

        $this->assertSame(8192, $limits->fileKb());
        $this->assertSame(24576, $limits->requestKb());
        $this->assertSame(8, $limits->fileMb());
        $this->assertFalse($limits->clampedByPhp());
    }

    public function test_one_file_can_never_exceed_the_request_ceiling(): void
    {
        $limits = new UploadLimits(8192, 1024, 10);

        $this->assertSame(1024, $limits->fileKb());
        $this->assertSame(1, $limits->fileMb());
    }

    public function test_current_reads_config_and_can_ignore_the_host(): void
    {
        config([
            'platform.media.max_file_kb' => 4096,
            'platform.media.max_upload_kb' => 10240,
            'platform.media.max_files_per_upload' => 5,
            'platform.media.respect_php_limits' => false,
        ]);

        $limits = UploadLimits::current();

        $this->assertSame(4096, $limits->fileKb());
        $this->assertSame(10240, $limits->requestKb());
        $this->assertSame(5, $limits->maxFiles());
        $this->assertFalse($limits->clampedByPhp());
    }
}
