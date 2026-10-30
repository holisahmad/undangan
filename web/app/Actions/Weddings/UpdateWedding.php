<?php

declare(strict_types=1);

namespace App\Actions\Weddings;

use App\Models\Wedding;

final class UpdateWedding
{
    /**
     * The internal title may change at any time. The public slug is only
     * editable while the wedding has never been published, because shared
     * links must keep working.
     */
    public function handle(Wedding $wedding, string $internalTitle): Wedding
    {
        $wedding->update(['internal_title' => $internalTitle]);

        return $wedding;
    }

    public function slugIsEditable(Wedding $wedding): bool
    {
        return ! $wedding->publications()->exists();
    }
}
