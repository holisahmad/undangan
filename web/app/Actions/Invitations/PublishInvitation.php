<?php

declare(strict_types=1);

namespace App\Actions\Invitations;

use App\Enums\WeddingLifecycleStatus;
use App\Exceptions\InvitationNotPublishable;
use App\Models\InvitationDraft;
use App\Models\InvitationPublication;
use App\Models\User;
use App\Models\Wedding;
use App\Support\Audit\AuditLogger;
use App\Support\Invitations\InvitationSnapshot;
use App\Support\Templates\InvitationContentValidator;
use App\Support\Templates\TemplateRegistry;
use Illuminate\Support\Facades\DB;

/**
 * Creates an immutable publication snapshot.
 *
 * Publishing is the only operation that changes what guests see, and every
 * requirement is verified server-side: content completeness, template
 * availability and an active invitation entitlement
 * (docs/API_SPEC.md, section 4 and docs/PAYMENT_FLOW.md, section 5).
 */
final class PublishInvitation
{
    public function __construct(
        private readonly InvitationSnapshot $snapshot,
        private readonly InvitationContentValidator $validator,
        private readonly TemplateRegistry $registry,
        private readonly AuditLogger $audit,
    ) {}

    public function handle(Wedding $wedding, User $actor): InvitationPublication
    {
        $draft = $wedding->draft()->with('templateVersion.template')->first();

        if ($draft === null) {
            throw InvitationNotPublishable::missingDraft();
        }

        $this->assertContentIsComplete($wedding, $draft);

        $templateVersion = $draft->templateVersion;

        if ($templateVersion === null
            || $templateVersion->status !== 'published'
            || ! $this->registry->supports($templateVersion->renderer_key)) {
            throw InvitationNotPublishable::templateNotAvailable();
        }

        $entitlement = $wedding->activeInvitationEntitlement();

        if ($entitlement === null) {
            throw InvitationNotPublishable::missingEntitlement();
        }

        $publishedAt = now();
        $expiresAt = $entitlement->expires_at;
        $snapshot = $this->snapshot->build($wedding, $draft, $templateVersion, $publishedAt, $expiresAt);

        return DB::transaction(function () use ($wedding, $actor, $snapshot, $publishedAt, $expiresAt): InvitationPublication {
            $versionNumber = ((int) $wedding->publications()->max('version_number')) + 1;

            $publication = InvitationPublication::create([
                'wedding_id' => $wedding->getKey(),
                'version_number' => $versionNumber,
                'published_snapshot' => $snapshot,
                'published_by' => $actor->getKey(),
                'published_at' => $publishedAt,
                'expires_at' => $expiresAt,
            ]);

            $wedding->update(['lifecycle_status' => WeddingLifecycleStatus::Published]);

            $this->audit->log(
                action: 'invitation.published',
                resourceType: 'wedding',
                resourceId: $wedding->getKey(),
                actor: $actor,
                metadata: [
                    'version_number' => $versionNumber,
                    'template_version_id' => $snapshot['template']['version'] ?? null,
                    'expires_at' => $expiresAt?->toIso8601String(),
                ],
            );

            return $publication;
        });
    }

    private function assertContentIsComplete(Wedding $wedding, InvitationDraft $draft): void
    {
        $missing = [];

        $partners = $wedding->partners;
        if ($partners->isEmpty()) {
            $missing[] = 'data mempelai';
        } elseif ($partners->contains(static fn ($partner): bool => trim((string) $partner->display_name) === '')) {
            $missing[] = 'nama mempelai';
        }

        if ($wedding->events->isEmpty()) {
            $missing[] = 'minimal satu acara';
        }

        if ($missing !== []) {
            throw InvitationNotPublishable::incompleteContent($missing);
        }

        // Re-validate stored content so data written by older code cannot break
        // a publication.
        $this->validator->validate(
            is_array($draft->content_json) ? $draft->content_json : [],
            $draft->templateVersion?->config_schema_version ?? InvitationContentValidator::SCHEMA_VERSION,
        );
    }
}
