<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Actions\Weddings\CreateWedding;
use App\Models\InvitationDraft;
use App\Models\TemplateVersion;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvitationDraft>
 */
class InvitationDraftFactory extends Factory
{
    protected $model = InvitationDraft::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'template_version_id' => TemplateVersion::factory(),
            'content_json' => CreateWedding::defaultContent(),
            'revision' => 1,
        ];
    }
}
