<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameMatchPlayer extends Model
{
    protected $fillable = [
        'game_match_id',
        'user_id',
        'game_room_player_id',
        'seat_no',
        'player_type',
        'bot_code',
        'finish_position',
        'score',
        'is_winner',
        'payout_amount',
        'status',
        'joined_at',
        'finished_at',
        'stats',
    ];

    protected function casts(): array
    {
        return [
            'is_winner' => 'boolean',
            'payout_amount' => 'decimal:4',
            'joined_at' => 'datetime',
            'finished_at' => 'datetime',
            'stats' => 'array',
        ];
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'game_match_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function roomPlayer(): BelongsTo
    {
        return $this->belongsTo(GameRoomPlayer::class, 'game_room_player_id');
    }
}
