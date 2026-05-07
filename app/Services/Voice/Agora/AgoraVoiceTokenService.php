<?php

namespace App\Services\Voice\Agora;

use App\Models\GameRoom;
use App\Models\PrivateLudoTable;
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
        $this->assertUserCanJoinChannel($user, $channelName);

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

    private function assertUserCanJoinChannel(User $user, string $channelName): void
    {
        if (str_starts_with($channelName, 'ludo_room_') || str_starts_with($channelName, 'ludo_tournament_')) {
            $roomUuid = str_contains($channelName, 'ludo_room_')
                ? substr($channelName, strlen('ludo_room_'))
                : substr($channelName, strlen('ludo_tournament_'));

            $roomExists = GameRoom::query()
                ->where('room_uuid', $roomUuid)
                ->whereHas('players', fn ($query) => $query->where('user_id', $user->id))
                ->exists();

            if (! $roomExists) {
                throw ValidationException::withMessages([
                    'channel' => ['Authenticated user is not part of the requested Ludo room.'],
                ]);
            }

            return;
        }

        if (str_starts_with($channelName, 'ludo_private_')) {
            $tableCode = strtoupper(substr($channelName, strlen('ludo_private_')));

            $tableExists = PrivateLudoTable::query()
                ->where('code', $tableCode)
                ->whereHas('players', fn ($query) => $query->where('user_id', $user->id))
                ->exists();

            if (! $tableExists) {
                throw ValidationException::withMessages([
                    'channel' => ['Authenticated user is not part of the requested private table.'],
                ]);
            }

            return;
        }

        throw ValidationException::withMessages([
            'channel' => ['Unsupported Agora voice channel prefix.'],
        ]);
    }
}
