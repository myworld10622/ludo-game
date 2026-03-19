<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentEntryResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'tournament_entry_id',
        'final_rank',
        'score',
        'prize_amount',
        'wallet_payout_transaction_id',
        'result_status',
        'meta',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'prize_amount' => 'decimal:2',
        'meta' => 'array',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(TournamentEntry::class, 'tournament_entry_id');
    }
}
