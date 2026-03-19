<?php

namespace App\Services\Auth;

use App\Models\AdminUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AdminAuthService
{
    public function attempt(array $credentials, bool $remember = true): AdminUser
    {
        if (! Auth::guard('admin')->attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid admin credentials provided.'],
            ]);
        }

        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();

        if (! $admin->is_active) {
            Auth::guard('admin')->logout();

            throw ValidationException::withMessages([
                'email' => ['This admin account is inactive.'],
            ]);
        }

        $admin->forceFill([
            'last_login_at' => now(),
        ])->save();

        return $admin;
    }

    public function logout(): void
    {
        Auth::guard('admin')->logout();
    }
}
