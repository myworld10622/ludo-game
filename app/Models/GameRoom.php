<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameRoom extends Model
{
    protected $fillable = [
        'room_uuid',
        'game_id',
        'queue_key',
        'room_type',
        'play_mode',
        'status',
        'max_players',
        'min_real_players',
        'current_players',
        'current_real_players',
        'current_bot_players',
        'entry_fee',
        'prize_pool',
        'allow_bots',
        'bot_fill_after_seconds',
        'started_with_bots',
        'game_mode',
        'node_namespace',
        'node_room_id',
        'registration_closed_at',
        'fill_bots_at',
        'started_at',
        'completed_at',
        'settings',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'entry_fee' => 'decimal:4',
            'prize_pool' => 'decimal:4',
            'allow_bots' => 'boolean',
            'started_with_bots' => 'boolean',
            'registration_closed_at' => 'datetime',
            'fill_bots_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'settings' => 'array',
            'meta' => 'array',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(GameRoomPlayer::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(GameRoomMessage::class, 'game_room_id');
    }
}
