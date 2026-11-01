<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MediaStatus;
use App\Models\MediaAsset;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MediaAsset>
 */
class MediaAssetFactory extends Factory
{
    protected $model = MediaAsset::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $key = 'weddings/'.Str::uuid().'/'.Str::random(16).'.jpg';

        return [
            'wedding_id' => Wedding::factory(),
            'album_id' => null,
            'uploaded_by_user_id' => null,
            'object_key' => $key,
            'preview_object_key' => str_replace('.jpg', '-preview.jpg', $key),
            'thumbnail_object_key' => str_replace('.jpg', '-thumb.jpg', $key),
            'original_filename' => 'foto.jpg',
            'mime_type' => 'image/jpeg',
            'checksum_sha256' => hash('sha256', Str::random(16)),
            'byte_size' => 1024,
            'width' => 1600,
            'height' => 1200,
            'status' => MediaStatus::Ready,
            'deleted_at' => null,
        ];
    }

    public function uploaded(): static
    {
        return $this->state(fn (): array => ['status' => MediaStatus::Uploaded]);
    }

    public function quarantined(): static
    {
        return $this->state(fn (): array => ['status' => MediaStatus::Quarantined]);
    }
}
