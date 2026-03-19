<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentMatchEntry extends Model
{
    protected $fillable = [
        'tournament_match_id',
        'tournament_entry_id',
        'user_id',
        'seat_no',
        'position',
        'score',
        'is_winner',
        'status',
        'stats',
        'joined_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'is_winner' => 'boolean',
            'stats' => 'array',
            'joined_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(TournamentMatch::class, 'tournament_match_id');
    }

    public function tournamentEntry(): BelongsTo
    {
        return $this->belongsTo(TournamentEntry::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
