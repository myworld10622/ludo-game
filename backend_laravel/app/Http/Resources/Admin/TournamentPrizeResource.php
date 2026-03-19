<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TournamentPrizeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rank_from' => $this->rank_from,
            'rank_to' => $this->rank_to,
            'prize_type' => $this->prize_type,
            'prize_amount' => $this->prize_amount,
            'prize_percent' => $this->prize_percent,
            'meta' => $this->meta,
        ];
    }
}
