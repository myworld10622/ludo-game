<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class TournamentPrizeResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rank_from' => $this->rank_from,
            'rank_to' => $this->rank_to,
            'prize_type' => $this->prize_type,
            'prize_amount' => (string) $this->prize_amount,
            'currency' => $this->currency,
        ];
    }
}
