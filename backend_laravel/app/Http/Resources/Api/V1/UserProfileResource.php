<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class UserProfileResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'date_of_birth' => optional($this->date_of_birth)?->toDateString(),
            'gender' => $this->gender,
            'country_code' => $this->country_code,
            'state' => $this->state,
            'city' => $this->city,
            'avatar_url' => $this->avatar_url,
            'language' => $this->language,
            'preferences' => $this->preferences,
        ];
    }
}
