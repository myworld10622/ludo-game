<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_success_redirects(): void
    {
        $admin = AdminUser::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'secret123',
        ])->assertRedirect('/admin');
    }

    public function test_admin_can_access_settings_when_authenticated(): void
    {
        $admin = AdminUser::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Super Admin',
            'email' => 'super@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings/app')
            ->assertStatus(200);
    }
}
