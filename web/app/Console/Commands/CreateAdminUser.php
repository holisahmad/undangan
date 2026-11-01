<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature = 'user:admin
                            {--name=Admin Wedly : Nama admin}
                            {--email=admin@undangan.test : Email admin}
                            {--password=admin123 : Password admin}';

    protected $description = 'Buat atau update user sebagai admin';

    public function handle(): int
    {
        $email    = $this->option('email');
        $name     = $this->option('name');
        $password = $this->option('password');

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name'              => $name,
                'password'          => Hash::make($password),
                'role'              => UserRole::Admin,
                'email_verified_at' => now(),
            ]
        );

        $action = $user->wasRecentlyCreated ? 'Dibuat' : 'Diperbarui';

        $this->table(
            ['Field', 'Value'],
            [
                ['ID',       $user->id],
                ['Nama',     $user->name],
                ['Email',    $user->email],
                ['Role',     $user->role->value],
                ['Status',   $action],
            ]
        );

        $this->info("✓ Admin user siap. Login dengan email: {$email} / password: {$password}");

        return self::SUCCESS;
    }
}
