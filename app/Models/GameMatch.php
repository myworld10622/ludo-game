<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameMatch extends Model
{
    protected $fillable = [
        'match_uuid',
        'game_id',
        'game_room_id',
        'winner_user_id',
        'status',
        'mode',
        'max_players',
        'real_players',
        'bot_players',
        'entry_fee',
        'prize_pool',
        'node_namespace',
        'node_room_id',
        'server_seed',
        'turn_state',
        'result_payload',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'entry_fee' => 'decimal:4',
            'prize_pool' => 'decimal:4',
            'turn_state' => 'array',
            'result_payload' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(GameRoom::class, 'game_room_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_user_id');
    }

    public function players(): HasMany
    {
        return $this->hasMany(GameMatchPlayer::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(GameRoomMessage::class, 'game_match_id');
    }
}
