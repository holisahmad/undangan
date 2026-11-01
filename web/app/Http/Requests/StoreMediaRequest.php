<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Wedding;
use App\Support\Media\UploadLimits;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

final class StoreMediaRequest extends FormRequest
{
    private ?UploadLimits $limits = null;

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

        $limits = $this->limits();
        $maxDimension = (int) config('platform.media.max_dimension', 8000);

        return [
            'album_id' => [
                'nullable',
                'uuid',
                Rule::exists('media_albums', 'id')->where('wedding_id', $wedding->getKey()),
            ],
            'photos' => ['required', 'array', 'min:1', 'max:'.$limits->maxFiles()],
            'photos.*' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:'.$limits->fileKb(),
                'dimensions:max_width='.$maxDimension.',max_height='.$maxDimension,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $limits = $this->limits();
        $serverNote = $limits->clampedByPhp() ? ' Batas ini mengikuti pengaturan server.' : '';

        return [
            'photos.required' => 'Pilih minimal satu foto.',
            'photos.max' => "Maksimal {$limits->maxFiles()} foto per unggahan.",
            'photos.*.image' => 'Semua berkas harus berupa gambar.',
            'photos.*.mimes' => 'Format foto harus JPG, PNG, atau WebP.',
            'photos.*.max' => "Ukuran tiap foto maksimal {$limits->fileMb()} MB.".$serverNote,
            // PHP drops a file over upload_max_filesize before Laravel can read it.
            'photos.*.uploaded' => 'Foto gagal diunggah; ukurannya melebihi batas server. Perkecil foto lalu coba lagi.',
            'photos.*.file' => 'Berkas tidak sampai ke server. Unggah lebih sedikit foto sekaligus.',
            'photos.*.dimensions' => 'Dimensi foto terlalu besar; perkecil dulu sebelum mengunggah.',
            'album_id.exists' => 'Album tidak ditemukan pada undangan ini.',
        ];
    }

    /**
     * The per-file rule cannot see the request as a whole, so the combined size
     * is checked here before the server returns a bare 413.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $files = $this->file('photos');

            if (! is_array($files)) {
                return;
            }

            $total = 0;

            foreach ($files as $file) {
                if ($file instanceof UploadedFile) {
                    $total += (int) $file->getSize();
                }
            }

            $limit = $this->limits()->requestBytes();

            if ($total > $limit) {
                $validator->errors()->add('photos', sprintf(
                    'Total ukuran yang dipilih %s MB, sedangkan server menerima maksimal %s MB sekali kirim. '
                        .'Unggah lebih sedikit foto sekaligus atau perkecil fotonya.',
                    UploadLimits::megabytes($total),
                    $this->limits()->requestMb(),
                ));
            }
        });
    }

    private function limits(): UploadLimits
    {
        return $this->limits ??= UploadLimits::current();
    }
}
