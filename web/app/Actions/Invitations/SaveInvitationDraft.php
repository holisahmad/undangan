<?php

declare(strict_types=1);

namespace App\Actions\Invitations;

use App\Models\InvitationDraft;
use App\Models\TemplateVersion;
use App\Models\Wedding;
use App\Support\Templates\InvitationContentValidator;
use App\Support\Templates\TemplateRegistry;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Saves the presentation payload of an invitation draft. Saving a draft never
 * touches a live publication (docs/PRD.md, section 4).
 */
final class SaveInvitationDraft
{
    public function __construct(
        private readonly InvitationContentValidator $validator,
        private readonly TemplateRegistry $registry,
    ) {}

    /**
     * @param  array<string, mixed>  $content
     */
    public function handle(Wedding $wedding, TemplateVersion $templateVersion, array $content): InvitationDraft
    {
        if (! $this->registry->supports($templateVersion->renderer_key)) {
            throw new InvalidArgumentException('Template tidak tersedia.');
        }

        if ($templateVersion->status !== 'published') {
            throw new InvalidArgumentException('Template belum dapat digunakan.');
        }

        $validated = $this->validator->validate($content, $templateVersion->config_schema_version);

        return DB::transaction(function () use ($wedding, $templateVersion, $validated): InvitationDraft {
            $draft = $wedding->draft()->lockForUpdate()->first();

            if ($draft === null) {
                return InvitationDraft::create([
                    'wedding_id' => $wedding->getKey(),
                    'template_version_id' => $templateVersion->getKey(),
                    'content_json' => $validated,
                    'revision' => 1,
                ]);
            }

            $draft->fill([
                'template_version_id' => $templateVersion->getKey(),
                'content_json' => $validated,
                'revision' => $draft->revision + 1,
            ])->save();

            return $draft;
        });
    }
}
