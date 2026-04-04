<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FriendRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'request_uuid' => $this->request_uuid,
            'status' => $this->status,
            'source' => $this->source,
            'source_room_uuid' => $this->source_room_uuid,
            'message' => $this->message,
            'sender' => $this->whenLoaded('sender', fn () => new UserResource($this->sender)),
            'receiver' => $this->whenLoaded('receiver', fn () => new UserResource($this->receiver)),
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
