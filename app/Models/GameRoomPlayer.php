<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameRoomPlayer extends Model
{
    protected $fillable = [
        'game_room_id',
        'user_id',
        'wallet_transaction_id',
        'seat_no',
        'player_type',
        'bot_code',
        'status',
        'is_host',
        'reconnect_token',
        'score',
        'finish_position',
        'payout_amount',
        'joined_at',
        'left_at',
        'last_seen_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_host' => 'boolean',
            'payout_amount' => 'decimal:4',
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(GameRoom::class, 'game_room_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }
}
