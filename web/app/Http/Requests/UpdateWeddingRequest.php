<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Wedding;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateWeddingRequest extends FormRequest
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
            'internal_title' => ['required', 'string', 'max:160'],
        ];
    }

    public function wedding(): Wedding
    {
        return $this->route('wedding');
    }
}
