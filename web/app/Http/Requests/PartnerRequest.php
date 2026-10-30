<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Wedding;
use App\Models\WeddingPartner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PartnerRequest extends FormRequest
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
            'partner_type' => [
                'required',
                Rule::in([WeddingPartner::TYPE_BRIDE, WeddingPartner::TYPE_GROOM, WeddingPartner::TYPE_OTHER]),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === WeddingPartner::TYPE_OTHER) {
                        return;
                    }

                    $existing = $this->wedding()->partners()
                        ->where('partner_type', $value)
                        ->when($this->partner() !== null, fn ($query) => $query->whereKeyNot($this->partner()->getKey()))
                        ->exists();

                    if ($existing) {
                        $fail('Data mempelai untuk peran ini sudah ada.');
                    }
                },
            ],
            'display_name' => ['required', 'string', 'max:120'],
            'nickname' => ['nullable', 'string', 'max:60'],
            'parents' => ['nullable', 'string', 'max:200'],
            'biography' => ['nullable', 'string', 'max:1000'],
            'instagram' => ['nullable', 'string', 'max:60'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:99'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $data = $this->validated();
        $data['instagram'] = isset($data['instagram']) && $data['instagram'] !== null
            ? ltrim(trim($data['instagram']), '@')
            : null;
        $data['display_order'] ??= 0;

        return $data;
    }

    public function wedding(): Wedding
    {
        return $this->route('wedding');
    }

    public function partner(): ?WeddingPartner
    {
        return $this->route('partner');
    }
}
