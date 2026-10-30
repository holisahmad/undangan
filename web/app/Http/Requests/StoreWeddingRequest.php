<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\TemplateVersion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreWeddingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'internal_title' => ['required', 'string', 'max:160'],
            'template_version_id' => [
                'required',
                'uuid',
                Rule::exists('template_versions', 'id')->where('status', 'published'),
            ],
        ];
    }

    public function templateVersion(): TemplateVersion
    {
        return TemplateVersion::with('template')->findOrFail($this->string('template_version_id')->toString());
    }
}
