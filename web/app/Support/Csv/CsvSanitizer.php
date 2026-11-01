<?php

declare(strict_types=1);

namespace App\Support\Csv;

/**
 * Neutralises spreadsheet formula injection on export. A cell starting with
 * one of these characters is prefixed with a single quote so spreadsheets
 * treat it as text (docs/SECURITY.md, section 4).
 */
final class CsvSanitizer
{
    /** @var list<string> */
    private const RISKY_PREFIXES = ['=', '+', '-', '@', "\t", "\r"];

    public static function cell(?string $value): string
    {
        $value = (string) $value;

        if ($value === '') {
            return '';
        }

        foreach (self::RISKY_PREFIXES as $prefix) {
            if (str_starts_with($value, $prefix)) {
                return "'".$value;
            }
        }

        return $value;
    }
}
