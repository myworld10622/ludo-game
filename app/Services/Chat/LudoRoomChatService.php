<?php

namespace App\Services\Chat;

use App\Models\GameRoom;
use App\Models\GameRoomMessage;
use App\Models\GameRoomPlayer;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LudoRoomChatService
{
    public function roomByUuid(string $roomUuid): GameRoom
    {
        return GameRoom::query()
            ->with(['players.user.profile', 'matches'])
            ->where('room_uuid', $roomUuid)
            ->firstOrFail();
    }

    public function authorizeRoomMember(GameRoom $room, User $user): GameRoomPlayer
    {
        $roomPlayer = $room->players
            ->first(fn (GameRoomPlayer $player) => (int) $player->user_id === (int) $user->id);

        if (! $roomPlayer) {
            throw new HttpException(403, 'You are not a participant of this room.');
        }

        return $roomPlayer;
    }

    public function listVisibleMessages(GameRoom $room, int $limit = 50): Collection
    {
        return GameRoomMessage::query()
            ->with(['room', 'match', 'user.profile'])
            ->where('game_room_id', $room->id)
            ->where('status', 'visible')
            ->latest('id')
            ->limit(max(1, min(100, $limit)))
            ->get()
            ->reverse()
            ->values();
    }

    public function createInternalMessage(GameRoom $room, array $attributes): GameRoomMessage
    {
        $senderType = (string) ($attributes['sender_type'] ?? 'human');
        $seatNo = $attributes['seat_no'] ?? null;
        $userId = $attributes['user_id'] ?? null;

        if ($senderType === 'human') {
            $roomPlayer = $this->resolveHumanRoomPlayer($room, $userId, $seatNo);
            if (! $roomPlayer) {
                throw new HttpException(422, 'Message sender is not an active room participant.');
            }

            $userId = $roomPlayer->user_id;
            $seatNo = $roomPlayer->seat_no;
        }

        $activeMatch = $room->matches()
            ->whereIn('status', ['starting', 'in_progress'])
            ->latest('id')
            ->first();

        $meta = is_array($attributes['meta'] ?? null) ? $attributes['meta'] : [];
        $meta['room_uuid'] = $room->room_uuid;
        if (! empty($attributes['bot_code'])) {
            $meta['bot_code'] = $attributes['bot_code'];
        }
        if (! empty($attributes['display_name'])) {
            $meta['display_name'] = $attributes['display_name'];
        }

        $message = GameRoomMessage::query()->create([
            'message_uuid' => (string) Str::uuid(),
            'game_room_id' => $room->id,
            'game_match_id' => $activeMatch?->id,
            'user_id' => $userId,
            'seat_no' => $seatNo,
            'sender_type' => $senderType,
            'message_type' => $attributes['message_type'] ?? 'text',
            'content' => trim((string) ($attributes['message'] ?? '')),
            'status' => 'visible',
            'meta' => $meta,
        ]);

        return $message->loadMissing(['room', 'match', 'user.profile']);
    }

    protected function resolveHumanRoomPlayer(GameRoom $room, mixed $userId, mixed $seatNo): ?GameRoomPlayer
    {
        return $room->players->first(function (GameRoomPlayer $player) use ($userId, $seatNo) {
            if ($player->player_type !== 'human') {
                return false;
            }

            if ($userId !== null && (int) $player->user_id === (int) $userId) {
                return true;
            }

            if ($seatNo !== null && (int) $player->seat_no === (int) $seatNo) {
                return true;
            }

            return false;
        });
    }
}
