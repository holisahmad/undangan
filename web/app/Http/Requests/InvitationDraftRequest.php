<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\TemplateVersion;
use App\Models\Wedding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Only the envelope is validated here. The presentation payload is validated
 * by InvitationContentValidator against the template's schema version, so the
 * rules live in one place (docs/ARCHITECTURE.md, section 6).
 */
final class InvitationDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->wedding()) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'template_version_id' => [
                'required',
                'uuid',
                Rule::exists('template_versions', 'id')->where('status', 'published'),
            ],
            'content' => ['required', 'array'],
        ];
    }

    public function wedding(): Wedding
    {
        return $this->route('wedding');
    }

    public function templateVersion(): TemplateVersion
    {
        return TemplateVersion::with('template')->findOrFail($this->string('template_version_id')->toString());
    }

    /**
     * @return array<string, mixed>
     */
    public function content(): array
    {
        return $this->validated('content');
    }
}
