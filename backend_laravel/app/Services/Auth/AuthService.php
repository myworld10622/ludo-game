<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthService
{
    public function __construct(
        protected ExternalIdentitySyncService $externalIdentitySyncService
    ) {
    }

    public function register(array $payload): array
    {
        return DB::transaction(function () use ($payload) {
            $referrer = null;

            if (! empty($payload['referral_code'])) {
                $referrer = User::query()->where('referral_code', $payload['referral_code'])->first();
            }

            $user = User::query()->create([
                'uuid' => (string) Str::uuid(),
                'username' => $payload['username'],
                'email' => $payload['email'] ?? null,
                'mobile' => $payload['mobile'] ?? null,
                'password' => Hash::make($payload['password']),
                'referral_code' => strtoupper(Str::random(8)),
                'referred_by_user_id' => $referrer?->id,
                'is_active' => true,
                'is_banned' => false,
            ]);

            $user->profile()->create($payload['profile'] ?? []);

            $token = $user->createToken($payload['device_name'] ?? 'mobile-app')->plainTextToken;

            $this->externalIdentitySyncService->queueRegistrationSync($user);

            return [
                'token' => $token,
                'user' => $user->load('profile'),
            ];
        });
    }

    public function login(array $payload): array
    {
        $user = User::query()
            ->where('username', $payload['identity'])
            ->orWhere('email', $payload['identity'])
            ->orWhere('mobile', $payload['identity'])
            ->first();

        if (! $user || ! Hash::check($payload['password'], $user->password)) {
            throw new HttpException(422, 'Invalid login credentials.');
        }

        if (! $user->is_active || $user->is_banned) {
            throw new HttpException(403, 'User account is not allowed to login.');
        }

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        $token = $user->createToken($payload['device_name'] ?? 'mobile-app')->plainTextToken;

        $this->externalIdentitySyncService->queueLoginSync($user);

        return [
            'token' => $token,
            'user' => $user->load('profile'),
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    public function profile(User $user): User
    {
        return $user->load('profile');
    }
}
