<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\UserSocialAccount;
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
                $referrer = $this->resolveReferrer((string) $payload['referral_code']);
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
            ->orWhere('user_code', $payload['identity'])
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

    public function socialLogin(array $payload): array
    {
        return DB::transaction(function () use ($payload) {
            $provider = Str::lower((string) $payload['provider']);
            $providerUserId = trim((string) $payload['provider_user_id']);
            $email = isset($payload['email']) ? Str::lower(trim((string) $payload['email'])) : null;
            $name = trim((string) ($payload['name'] ?? ''));
            $avatarUrl = trim((string) ($payload['avatar_url'] ?? ''));

            $socialAccount = UserSocialAccount::query()
                ->with('user.profile')
                ->where('provider', $provider)
                ->where('provider_user_id', $providerUserId)
                ->first();

            $user = $socialAccount?->user;

            if (! $user && $email !== null && $email !== '') {
                $user = User::query()->where('email', $email)->first();
            }

            if (! $user) {
                $user = User::query()->create([
                    'uuid' => (string) Str::uuid(),
                    'username' => $this->generateUniqueUsername($provider, $name, $email),
                    'email' => $email ?: null,
                    'password' => Hash::make(Str::random(40)),
                    'referral_code' => strtoupper(Str::random(8)),
                    'is_active' => true,
                    'is_banned' => false,
                    'email_verified_at' => $email ? now() : null,
                ]);

                $user->profile()->create([
                    'first_name' => $name !== '' ? $name : Str::headline($provider).' Player',
                    'avatar_url' => $avatarUrl !== '' ? $avatarUrl : null,
                    'language' => 'en',
                ]);
            } elseif (! $user->is_active || $user->is_banned) {
                throw new HttpException(403, 'User account is not allowed to login.');
            }

            if (! $socialAccount) {
                $socialAccount = UserSocialAccount::query()->create([
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'provider_user_id' => $providerUserId,
                    'provider_email' => $email ?: null,
                    'provider_name' => $name !== '' ? $name : null,
                    'avatar_url' => $avatarUrl !== '' ? $avatarUrl : null,
                ]);
            } else {
                $socialAccount->forceFill([
                    'provider_email' => $email ?: $socialAccount->provider_email,
                    'provider_name' => $name !== '' ? $name : $socialAccount->provider_name,
                    'avatar_url' => $avatarUrl !== '' ? $avatarUrl : $socialAccount->avatar_url,
                ])->save();
            }

            if ($user->profile && $avatarUrl !== '' && empty($user->profile->avatar_url)) {
                $user->profile->forceFill([
                    'avatar_url' => $avatarUrl,
                ])->save();
            }

            $user->forceFill([
                'last_login_at' => now(),
                'email_verified_at' => $email ? ($user->email_verified_at ?? now()) : $user->email_verified_at,
            ])->save();

            $token = $user->createToken($payload['device_name'] ?? 'mobile-app')->plainTextToken;

            $this->externalIdentitySyncService->queueLoginSync($user);

            return [
                'token' => $token,
                'user' => $user->load('profile', 'socialAccounts'),
            ];
        });
    }

    public function profile(User $user): User
    {
        return $user->load('profile');
    }

    protected function resolveReferrer(string $referralCode): ?User
    {
        $normalizedCode = strtoupper(trim($referralCode));
        $normalizedCode = preg_replace('/^777-/i', '', $normalizedCode);

        if ($normalizedCode === '') {
            return null;
        }

        return User::query()
            ->where('referral_code', $normalizedCode)
            ->orWhere('user_code', $normalizedCode)
            ->first();
    }

    protected function generateUniqueUsername(string $provider, string $name, ?string $email): string
    {
        $base = $name !== ''
            ? Str::slug($name, '_')
            : ($email ? Str::before($email, '@') : $provider.'_user');

        $base = trim($base, '_');
        if ($base === '') {
            $base = $provider.'_user';
        }

        $base = Str::lower(substr($base, 0, 24));
        $candidate = $base;
        $attempts = 0;

        while (User::query()->where('username', $candidate)->exists()) {
            $attempts++;
            $candidate = substr($base, 0, 18).'_'.random_int(1000, 999999);

            if ($attempts > 10) {
                $candidate = $provider.'_'.Str::lower(Str::random(10));
                break;
            }
        }

        return $candidate;
    }
}
