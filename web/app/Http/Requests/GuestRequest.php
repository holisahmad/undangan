<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Wedding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared by create and edit: the fields and limits are identical, and the
 * group must belong to the same wedding (a foreign UUID is never trusted).
 */
final class GuestRequest extends FormRequest
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
            'display_name' => ['required', 'string', 'max:120'],
            'guest_group_id' => [
                'nullable',
                'uuid',
                Rule::exists('guest_groups', 'id')->where('wedding_id', $wedding->getKey()),
            ],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'contact_email' => ['nullable', 'email', 'max:190'],
            'allowed_party_size' => [
                'required',
                'integer',
                'min:1',
                'max:'.(int) config('platform.guests.max_party_size', 20),
            ],
        ];
    }

    /**
     * @return array{display_name: string, guest_group_id: string|null, contact_phone: string|null, contact_email: string|null, allowed_party_size: int}
     */
    public function guestData(): array
    {
        return [
            'display_name' => trim((string) $this->input('display_name')),
            'guest_group_id' => $this->input('guest_group_id') ?: null,
            'contact_phone' => $this->optionalString('contact_phone'),
            'contact_email' => $this->optionalString('contact_email'),
            'allowed_party_size' => (int) $this->input('allowed_party_size'),
        ];
    }

    private function optionalString(string $key): ?string
    {
        $value = trim((string) $this->input($key, ''));

        return $value === '' ? null : $value;
    }
}
