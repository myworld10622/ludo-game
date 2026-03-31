<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentPrize extends Model
{
    protected $fillable = [
        'tournament_id',
        'position',
        'prize_pct',
        'prize_amount',
        'winner_user_id',
        'payout_status',
        'paid_at',
    ];

    protected $casts = [
        'prize_pct'    => 'decimal:2',
        'prize_amount' => 'decimal:2',
        'paid_at'      => 'datetime',
    ];

    const PAYOUT_PENDING   = 'pending';
    const PAYOUT_PAID      = 'paid';
    const PAYOUT_DISPUTED  = 'disputed';
    const PAYOUT_FORFEITED = 'forfeited';
    const PAYOUT_CASCADED  = 'cascaded'; // bot won, prize went to next real player

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_user_id');
    }
}
