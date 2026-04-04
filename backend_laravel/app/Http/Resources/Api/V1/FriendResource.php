<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FriendResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'status' => $this->status,
            'friendship_created_at' => optional($this->created_at)->toIso8601String(),
            'friend' => $this->whenLoaded('friend', fn () => new UserResource($this->friend)),
        ];
    }
}
