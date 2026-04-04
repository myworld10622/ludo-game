<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Social\RespondFriendRequestRequest;
use App\Http\Requests\Api\V1\Social\SendFriendRequestByPlayerIdRequest;
use App\Http\Requests\Api\V1\Social\SendFriendRequestRequest;
use App\Http\Requests\Api\V1\Social\SearchUserByPlayerIdRequest;
use App\Http\Resources\Api\V1\FriendRequestResource;
use App\Http\Resources\Api\V1\FriendResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\Social\FriendService;

class FriendController extends Controller
{
    public function __construct(
        protected FriendService $friendService
    ) {
    }

    public function index()
    {
        return $this->successResponse(
            FriendResource::collection($this->friendService->listFriends(auth()->user())),
            'Friends fetched successfully.'
        );
    }

    public function requests()
    {
        return $this->successResponse(
            FriendRequestResource::collection($this->friendService->listRequests(auth()->user())),
            'Friend requests fetched successfully.'
        );
    }

    public function searchByPlayerId(SearchUserByPlayerIdRequest $request, string $playerId)
    {
        $validated = $request->validated();
        $user = $this->friendService->findUserByPlayerId($validated['playerId']);

        return $this->successResponse(
            new UserResource($user->loadMissing('profile')),
            'User fetched successfully.'
        );
    }

    public function send(SendFriendRequestRequest $request)
    {
        $receiver = User::query()->findOrFail($request->validated()['receiver_user_id']);
        $friendRequest = $this->friendService->sendRequest(
            $request->user(),
            $receiver,
            $request->validated()
        );

        return $this->successResponse(
            new FriendRequestResource($friendRequest),
            $friendRequest->status === 'accepted'
                ? 'Friend request auto-accepted successfully.'
                : 'Friend request sent successfully.'
        );
    }

    public function sendByPlayerId(SendFriendRequestByPlayerIdRequest $request)
    {
        $validated = $request->validated();
        $receiver = $this->friendService->findUserByPlayerId($validated['player_id']);
        $friendRequest = $this->friendService->sendRequest(
            $request->user(),
            $receiver,
            $validated
        );

        return $this->successResponse(
            new FriendRequestResource($friendRequest),
            $friendRequest->status === 'accepted'
                ? 'Friend request auto-accepted successfully.'
                : 'Friend request sent successfully.'
        );
    }

    public function respond(RespondFriendRequestRequest $request, string $requestUuid)
    {
        $action = $request->validated()['action']
            ?? $request->route('action')
            ?? 'accept';

        $friendRequest = $this->friendService->respondToRequest(
            $request->user(),
            $requestUuid,
            $action
        );

        return $this->successResponse(
            new FriendRequestResource($friendRequest),
            $friendRequest->status === 'accepted'
                ? 'Friend request accepted successfully.'
                : 'Friend request rejected successfully.'
        );
    }
}
