<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class HomeResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'visible_games' => GameResource::collection(collect($this->resource['visible_games'] ?? []))->resolve(),
            'wallet_summary' => [
                'balance' => (string) ($this->resource['wallet_summary']['balance'] ?? '0.0000'),
                'locked_balance' => (string) ($this->resource['wallet_summary']['locked_balance'] ?? '0.0000'),
                'currency' => $this->resource['wallet_summary']['currency'] ?? 'INR',
            ],
            'shortcuts' => [
                'deposit_enabled' => (bool) ($this->resource['shortcuts']['deposit_enabled'] ?? false),
                'withdraw_enabled' => (bool) ($this->resource['shortcuts']['withdraw_enabled'] ?? false),
                'history_enabled' => (bool) ($this->resource['shortcuts']['history_enabled'] ?? false),
                'rewards_enabled' => (bool) ($this->resource['shortcuts']['rewards_enabled'] ?? false),
                'support_enabled' => (bool) ($this->resource['shortcuts']['support_enabled'] ?? false),
            ],
        ];
    }
}
