<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ArchiveStatus;
use App\Models\ArchiveJob;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ArchiveJob>
 */
class ArchiveJobFactory extends Factory
{
    protected $model = ArchiveJob::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'requested_by_user_id' => null,
            'status' => ArchiveStatus::Ready,
            'asset_count' => 1,
            'byte_size' => 1024,
            'object_key' => 'archives/'.Str::uuid().'.zip',
            'manifest_json' => [['filename' => 'foto.jpg', 'byte_size' => 1024]],
            'error_message' => null,
            'expires_at' => now()->addDays(7),
            'started_at' => now()->subMinute(),
            'finished_at' => now(),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'expires_at' => now()->subMinute(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (): array => [
            'status' => ArchiveStatus::Failed,
            'object_key' => null,
            'manifest_json' => null,
            'error_message' => 'Disk penuh',
        ]);
    }
}
