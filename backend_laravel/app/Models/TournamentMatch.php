<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentMatch extends Model
{
    protected $fillable = [
        'match_uuid',
        'tournament_id',
        'game_id',
        'round_no',
        'match_no',
        'bracket_position',
        'stage',
        'status',
        'winner_entry_id',
        'max_players',
        'table_fee',
        'node_room_id',
        'external_match_ref',
        'scheduled_at',
        'started_at',
        'completed_at',
        'settings',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'table_fee' => 'decimal:4',
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'settings' => 'array',
            'meta' => 'array',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function winnerEntry(): BelongsTo
    {
        return $this->belongsTo(TournamentEntry::class, 'winner_entry_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(TournamentMatchEntry::class);
    }

    public function activeEntries(): HasMany
    {
        return $this->hasMany(TournamentMatchEntry::class)->where('status', '!=', 'completed');
    }
}
