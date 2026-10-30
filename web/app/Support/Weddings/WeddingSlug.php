<?php

declare(strict_types=1);

namespace App\Support\Weddings;

use App\Models\Wedding;
use Illuminate\Support\Str;

/**
 * Public invitation URLs are /u/{slug}. Slugs are generated once and become
 * immutable after the first publication so shared links never break
 * (docs/UI_UX_FLOWS.md, section 2).
 */
final class WeddingSlug
{
    public static function generate(string $title): string
    {
        $base = Str::slug(Str::limit($title, 60, ''));

        if ($base === '') {
            $base = 'undangan';
        }

        do {
            $candidate = $base.'-'.Str::lower(Str::random(6));
        } while (Wedding::where('slug', $candidate)->exists());

        return $candidate;
    }
}
