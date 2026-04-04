<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Ludo\ListRoomMessagesRequest;
use App\Http\Resources\Api\V1\GameRoomMessageResource;
use App\Services\Chat\LudoRoomChatService;

class LudoRoomMessageController extends Controller
{
    public function __construct(
        protected LudoRoomChatService $chatService
    ) {
    }

    public function index(ListRoomMessagesRequest $request, string $roomUuid)
    {
        $room = $this->chatService->roomByUuid($roomUuid);
        $this->chatService->authorizeRoomMember($room, $request->user());

        $messages = $this->chatService->listVisibleMessages(
            $room,
            (int) ($request->validated()['limit'] ?? 50)
        );

        return $this->successResponse(
            GameRoomMessageResource::collection($messages),
            'Ludo room messages fetched successfully.'
        );
    }
}
