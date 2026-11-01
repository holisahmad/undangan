<?php

declare(strict_types=1);

namespace App\Actions\Invitations;

use App\Models\InvitationDraft;
use App\Models\TemplateVersion;
use App\Models\Wedding;
use App\Support\Billing\TemplateAccess;
use App\Support\Templates\InvitationContentValidator;
use App\Support\Templates\TemplatePresentation;
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
        private readonly TemplateAccess $access,
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

        $this->assertTierAllowsSwitch($wedding, $templateVersion);

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

    /**
     * A premium design can only be *moved onto* once the paid package unlocks it.
     * A draft that already points at that design keeps saving, because the theme
     * was chosen while the choice was still free and blocking the text editor over
     * a billing decision would strand the owner mid-sentence. Publication is where
     * the ceiling becomes final (see PublishInvitation).
     */
    private function assertTierAllowsSwitch(Wedding $wedding, TemplateVersion $templateVersion): void
    {
        $template = $templateVersion->template;
        $draft = $wedding->draft;

        if ($template === null) {
            // The catalogue no longer lists the version, so there is no tier to
            // compare against; the renderer check above is what protects the page.
            return;
        }

        if ($draft !== null && (string) $draft->template_version_id === (string) $templateVersion->getKey()) {
            return;
        }

        if ($this->access->allows($this->access->ceilingFor($wedding), $template)) {
            return;
        }

        throw new InvalidArgumentException(
            'Tema '.$template->name.' hanya tersedia pada '.TemplatePresentation::tierLabel($template->tier).'.',
        );
    }
}
