<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentMatch extends Model
{
    protected $fillable = [
        'tournament_id',
        'round_number',
        'match_number',
        'room_id',
        'status',
        'winner_registration_id',
        'forced_winner_registration_id',
        'player_scores',
        'game_log',
        'is_admin_override',
        'admin_override_note',
        'scheduled_at',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'player_scores'     => 'array',
        'game_log'          => 'array',
        'is_admin_override' => 'boolean',
        'scheduled_at'      => 'datetime',
        'started_at'        => 'datetime',
        'ended_at'          => 'datetime',
    ];

    const STATUS_SCHEDULED  = 'scheduled';
    const STATUS_WAITING    = 'waiting';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED  = 'completed';
    const STATUS_CANCELLED  = 'cancelled';
    const STATUS_DISPUTED   = 'disputed';
    const STATUS_FORFEITED  = 'forfeited';

    // ── Relationships ─────────────────────────────────────────────────────────

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(TournamentRegistration::class, 'winner_registration_id');
    }

    public function forcedWinner(): BelongsTo
    {
        return $this->belongsTo(TournamentRegistration::class, 'forced_winner_registration_id');
    }

    public function players(): HasMany
    {
        return $this->hasMany(TournamentMatchPlayer::class, 'match_id')->orderBy('slot_number');
    }
}
