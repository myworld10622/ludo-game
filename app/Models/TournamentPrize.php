<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentPrize extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'rank_from',
        'rank_to',
        'prize_type',
        'prize_amount',
        'prize_percent',
        'meta',
    ];

    protected $casts = [
        'prize_amount' => 'decimal:2',
        'prize_percent' => 'decimal:2',
        'meta' => 'array',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }
}
