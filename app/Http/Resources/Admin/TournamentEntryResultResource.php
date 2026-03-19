<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TournamentEntryResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tournament_id' => $this->tournament_id,
            'tournament_entry_id' => $this->tournament_entry_id,
            'entry' => $this->whenLoaded('entry', fn () => [
                'id' => $this->entry->id,
                'uuid' => $this->entry->uuid,
                'ticket_no' => $this->entry->ticket_no,
                'user_id' => $this->entry->user_id,
            ]),
            'final_rank' => $this->final_rank,
            'score' => $this->score,
            'prize_amount' => $this->prize_amount,
            'wallet_payout_transaction_id' => $this->wallet_payout_transaction_id,
            'result_status' => $this->result_status,
            'meta' => $this->meta,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
