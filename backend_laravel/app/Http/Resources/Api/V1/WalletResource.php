<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class WalletResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'wallet_type' => $this->wallet_type,
            'currency' => $this->currency,
            'balance' => (string) $this->balance,
            'locked_balance' => (string) $this->locked_balance,
            'is_active' => (bool) $this->is_active,
            'last_transaction_at' => optional($this->last_transaction_at)?->toIso8601String(),
        ];
    }
}
