<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrivateLudoTable extends Model
{
    public const CODE_LENGTH = 6;

    protected $fillable = [
        'code', 'creator_id', 'fee_amount', 'max_players',
        'current_players', 'prize_pool', 'status',
        'winner_id', 'winner_prize', 'started_at', 'expires_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    public function players(): HasMany
    {
        return $this->hasMany(PrivateLudoTablePlayer::class, 'private_table_id');
    }

    public function isFull(): bool
    {
        return $this->current_players >= $this->max_players;
    }

    public static function generateCode(): string
    {
        do {
            $code = str_pad((string) random_int(0, (10 ** self::CODE_LENGTH) - 1), self::CODE_LENGTH, '0', STR_PAD_LEFT);
        } while (self::where('code', $code)->exists());

        return $code;
    }
}
