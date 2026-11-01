<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\AlbumVisibility;
use App\Models\MediaAlbum;
use App\Models\Wedding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateMediaAlbumRequest extends FormRequest
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
        /** @var MediaAlbum $album */
        $album = $this->route('mediaAlbum');

        return [
            'title' => [
                'sometimes',
                'string',
                'max:80',
                Rule::unique('media_albums', 'title')
                    ->where('wedding_id', $wedding->getKey())
                    ->ignore($album->getKey()),
            ],
            'visibility' => ['required', Rule::enum(AlbumVisibility::class)],
        ];
    }
}
