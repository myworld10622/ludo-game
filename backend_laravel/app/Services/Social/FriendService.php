<?php

namespace App\Services\Social;

use App\Models\FriendRequest;
use App\Models\User;
use App\Models\UserFriend;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FriendService
{
    public function listFriends(User $user): Collection
    {
        return UserFriend::query()
            ->with(['friend.profile'])
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->latest('id')
            ->get();
    }

    public function listRequests(User $user): Collection
    {
        return FriendRequest::query()
            ->with(['sender.profile', 'receiver.profile'])
            ->where(function ($query) use ($user) {
                $query->where('sender_user_id', $user->id)
                    ->orWhere('receiver_user_id', $user->id);
            })
            ->latest('id')
            ->get();
    }

    public function findUserByPlayerId(string $playerId): User
    {
        $query = trim($playerId);

        return User::query()
            ->with('profile')
            ->where(function ($builder) use ($query) {
                $builder->where('id', $query)
                    ->orWhere('uuid', $query)
                    ->orWhere('user_code', $query)
                    ->orWhere('username', $query)
                    ->orWhere('email', $query)
                    ->orWhere('mobile', $query);
            })
            ->firstOrFail();
    }

    public function sendRequest(User $sender, User $receiver, array $attributes = []): FriendRequest
    {
        if ((int) $sender->id === (int) $receiver->id) {
            throw new HttpException(422, 'You cannot send a friend request to yourself.');
        }

        if ($this->areFriends($sender->id, $receiver->id)) {
            throw new HttpException(409, 'You are already friends.');
        }

        return DB::transaction(function () use ($sender, $receiver, $attributes) {
            $inversePending = FriendRequest::query()
                ->where('sender_user_id', $receiver->id)
                ->where('receiver_user_id', $sender->id)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if ($inversePending) {
                return $this->acceptRequest($sender, $inversePending);
            }

            $existingPending = FriendRequest::query()
                ->where('sender_user_id', $sender->id)
                ->where('receiver_user_id', $receiver->id)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if ($existingPending) {
                throw new HttpException(409, 'Friend request already pending.');
            }

            $request = FriendRequest::query()->create([
                'request_uuid' => (string) Str::uuid(),
                'sender_user_id' => $sender->id,
                'receiver_user_id' => $receiver->id,
                'status' => 'pending',
                'source' => $attributes['source'] ?? 'profile',
                'source_room_uuid' => $attributes['source_room_uuid'] ?? null,
                'message' => $attributes['message'] ?? null,
                'meta' => $attributes['meta'] ?? null,
            ]);

            return $request->loadMissing(['sender.profile', 'receiver.profile']);
        });
    }

    public function respondToRequest(User $actor, string $requestUuid, string $action): FriendRequest
    {
        $request = FriendRequest::query()
            ->with(['sender.profile', 'receiver.profile'])
            ->where('request_uuid', $requestUuid)
            ->firstOrFail();

        if ((int) $request->receiver_user_id !== (int) $actor->id) {
            throw new HttpException(403, 'You cannot respond to this friend request.');
        }

        if ($request->status !== 'pending') {
            throw new HttpException(409, 'Friend request is no longer pending.');
        }

        return $action === 'accept'
            ? $this->acceptRequest($actor, $request)
            : $this->rejectRequest($request);
    }

    protected function acceptRequest(User $actor, FriendRequest $request): FriendRequest
    {
        return DB::transaction(function () use ($actor, $request) {
            $request->forceFill([
                'status' => 'accepted',
            ])->save();

            $this->createFriendshipPair($request->sender_user_id, $request->receiver_user_id);

            return $request->fresh(['sender.profile', 'receiver.profile']);
        });
    }

    protected function rejectRequest(FriendRequest $request): FriendRequest
    {
        $request->forceFill([
            'status' => 'rejected',
        ])->save();

        return $request->fresh(['sender.profile', 'receiver.profile']);
    }

    protected function createFriendshipPair(int $userId, int $friendUserId): void
    {
        UserFriend::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'friend_user_id' => $friendUserId,
            ],
            [
                'status' => 'active',
            ]
        );

        UserFriend::query()->updateOrCreate(
            [
                'user_id' => $friendUserId,
                'friend_user_id' => $userId,
            ],
            [
                'status' => 'active',
            ]
        );
    }

    protected function areFriends(int $leftUserId, int $rightUserId): bool
    {
        return UserFriend::query()
            ->where('user_id', $leftUserId)
            ->where('friend_user_id', $rightUserId)
            ->where('status', 'active')
            ->exists();
    }
}
