<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Wedding;
use Illuminate\Foundation\Http\FormRequest;

final class ImportGuestsRequest extends FormRequest
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
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:512'],
        ];
    }
}
