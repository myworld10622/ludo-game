<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentMatchPlayer extends Model
{
    protected $fillable = [
        'match_id',
        'registration_id',
        'slot_number',
        'score',
        'finish_position',
        'result',
        'joined_at',
        'finished_at',
    ];

    protected $casts = [
        'joined_at'   => 'datetime',
        'finished_at' => 'datetime',
    ];

    const RESULT_WIN          = 'win';
    const RESULT_LOSS         = 'loss';
    const RESULT_DRAW         = 'draw';
    const RESULT_FORFEIT      = 'forfeit';
    const RESULT_DISCONNECTED = 'disconnected';

    public function match(): BelongsTo
    {
        return $this->belongsTo(TournamentMatch::class, 'match_id');
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(TournamentRegistration::class, 'registration_id');
    }
}
