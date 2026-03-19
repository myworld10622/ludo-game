<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class WalletTransactionResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_uuid' => $this->transaction_uuid,
            'type' => $this->type,
            'direction' => $this->direction,
            'status' => $this->status,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'opening_balance' => (string) $this->balance_before,
            'amount' => (string) $this->amount,
            'closing_balance' => (string) $this->balance_after,
            'currency' => $this->currency,
            'description' => $this->description,
            'processed_at' => optional($this->processed_at)?->toIso8601String(),
            'game' => $this->whenLoaded('game', fn () => [
                'id' => $this->game?->id,
                'code' => $this->game?->code,
                'name' => $this->game?->name,
            ]),
            'tournament' => $this->whenLoaded('tournament', fn () => $this->tournament ? [
                'id' => $this->tournament->id,
                'name' => $this->tournament->name,
                'code' => $this->tournament->code,
            ] : null),
            'meta' => $this->meta,
        ];
    }
}
