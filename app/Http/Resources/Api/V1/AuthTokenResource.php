<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class AuthTokenResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->resource['token'],
            'token_type' => 'Bearer',
            'user' => new UserResource($this->resource['user']),
        ];
    }
}
