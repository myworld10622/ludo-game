<?php

namespace App\Services\Voice\Agora;

use App\Models\User;
use CyberDeep\LaravelAgoraTokenGenerator\Services\Agora;
use Illuminate\Validation\ValidationException;

class AgoraVoiceTokenService
{
    private const CHANNEL_PATTERN = '/^[A-Za-z0-9 !#$%&()+,\-.:;<=>?@\[\]^_{|}~]{1,64}$/';

    public function issueForUser(User $user, string $channelName, ?int $requestedUid = null): array
    {
        $this->assertConfigured();
        $this->assertValidChannelName($channelName);
        $this->assertUidMatchesAuthenticatedUser($user, $requestedUid);
        $this->assertValidChannelPrefix($channelName);

        $uid = (int) $user->id;
        $expiresIn = max(1, (int) env('AGORA_TOKEN_EXPIRE_SECONDS', 3600));
        $joinAsSubscriber = (string) env('AGORA_DEFAULT_ROLE', 'publisher') === 'subscriber';

        $token = Agora::make($uid)
            ->channel($channelName)
            ->uId((string) $uid)
            ->join($joinAsSubscriber)
            ->audioOnly(true)
            ->token();

        if ($token === '') {
            throw ValidationException::withMessages([
                'agora' => ['Agora token generation failed. Check App ID and App Certificate values.'],
            ]);
        }

        return [
            'appId' => (string) config('laravel-agora-token-generator.agora.app_id'),
            'token' => $token,
            'channel' => $channelName,
            'uid' => $uid,
            'expiresIn' => $expiresIn,
        ];
    }

    private function assertConfigured(): void
    {
        if (
            blank(config('laravel-agora-token-generator.agora.app_id'))
            || blank(config('laravel-agora-token-generator.agora.app_certificate'))
        ) {
            throw ValidationException::withMessages([
                'agora' => ['Agora credentials are missing in backend configuration.'],
            ]);
        }
    }

    private function assertValidChannelName(string $channelName): void
    {
        if (! preg_match(self::CHANNEL_PATTERN, $channelName)) {
            throw ValidationException::withMessages([
                'channel' => ['Channel name is invalid for Agora token generation.'],
            ]);
        }
    }

    private function assertUidMatchesAuthenticatedUser(User $user, ?int $requestedUid): void
    {
        if ($requestedUid !== null && $requestedUid !== (int) $user->id) {
            throw ValidationException::withMessages([
                'uid' => ['Requested uid does not match the authenticated user.'],
            ]);
        }
    }

    // JWT auth already establishes the caller identity.
    // Socket/private-table identifiers are not guaranteed to be backed by Laravel rows,
    // so live token issuance only validates that the requested channel uses an allowed prefix.
    private function assertValidChannelPrefix(string $channelName): void
    {
        if (
            str_starts_with($channelName, 'ludo_room_')
            || str_starts_with($channelName, 'ludo_tournament_')
            || str_starts_with($channelName, 'ludo_private_')
        ) {
            return;
        }

        throw ValidationException::withMessages([
            'channel' => ['Unsupported Agora voice channel prefix.'],
        ]);
    }
}
