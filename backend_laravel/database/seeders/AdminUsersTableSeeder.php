<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUsersTableSeeder extends Seeder
{
    public function run(): void
    {
        AdminUser::query()->updateOrCreate(
            ['email' => env('ADMIN_SEED_EMAIL', 'admin@example.com')],
            [
                'uuid' => (string) Str::uuid(),
                'name' => env('ADMIN_SEED_NAME', 'Super Admin'),
                'password' => Hash::make(env('ADMIN_SEED_PASSWORD', 'password123')),
                'role' => 'super_admin',
                'is_active' => true,
            ]
        );
    }
}
