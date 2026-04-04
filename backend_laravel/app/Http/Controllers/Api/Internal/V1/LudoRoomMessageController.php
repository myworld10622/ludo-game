<?php

namespace App\Http\Controllers\Api\Internal\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Internal\V1\Ludo\CreateRoomMessageRequest;
use App\Http\Requests\Api\Internal\V1\Ludo\ListRoomMessagesRequest;
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
        $messages = $this->chatService->listVisibleMessages(
            $room,
            (int) ($request->validated()['limit'] ?? 50)
        );

        return $this->successResponse(
            GameRoomMessageResource::collection($messages),
            'Ludo room messages fetched successfully.'
        );
    }

    public function store(CreateRoomMessageRequest $request, string $roomUuid)
    {
        $room = $this->chatService->roomByUuid($roomUuid);
        $message = $this->chatService->createInternalMessage($room, $request->validated());

        return $this->successResponse(
            new GameRoomMessageResource($message),
            'Ludo room message created successfully.'
        );
    }
}
