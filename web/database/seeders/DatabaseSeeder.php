<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(TemplateSeeder::class);

        // Demo credentials are only created on local machines. Production
        // accounts are never seeded (docs/SECURITY.md, section 6).
        if (app()->environment('local')) {
            User::factory()->create([
                'name' => 'Demo Pengantin',
                'email' => 'demo@undangan.test',
            ]);
        }
    }
}
