<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Wedding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreGuestGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        $wedding = $this->route('wedding');

        return $this->user() !== null
            && $wedding instanceof Wedding
            && $this->user()->can('update', $wedding);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Wedding $wedding */
        $wedding = $this->route('wedding');

        return [
            'name' => [
                'required',
                'string',
                'max:60',
                Rule::unique('guest_groups', 'name')->where('wedding_id', $wedding->getKey()),
            ],
        ];
    }
}
