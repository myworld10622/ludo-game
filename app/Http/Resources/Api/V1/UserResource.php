<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class UserResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_code' => $this->user_code,
            'uuid' => $this->uuid,
            'username' => $this->username,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'referral_code' => $this->referral_code,
            'is_active' => (bool) $this->is_active,
            'is_banned' => (bool) $this->is_banned,
            'last_login_at' => optional($this->last_login_at)?->toIso8601String(),
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'profile' => $this->whenLoaded('profile', fn () => new UserProfileResource($this->profile)),
        ];
    }
}
