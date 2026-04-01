<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentRegistration extends Model
{
    protected $fillable = [
        'tournament_id',
        'user_id',
        'is_bot',
        'bot_difficulty',
        'bot_name',
        'seed_number',
        'entry_fee_paid',
        'status',
        'final_position',
        'prize_won',
        'registered_at',
        'last_checked_in_at',
        'last_checked_in_slot_index',
        'eliminated_at',
        'completed_at',
    ];

    protected $casts = [
        'is_bot'         => 'boolean',
        'entry_fee_paid' => 'decimal:2',
        'prize_won'      => 'decimal:2',
        'registered_at'  => 'datetime',
        'last_checked_in_at' => 'datetime',
        'eliminated_at'  => 'datetime',
        'completed_at'   => 'datetime',
    ];

    const STATUS_REGISTERED    = 'registered';
    const STATUS_CHECKED_IN    = 'checked_in';
    const STATUS_PLAYING       = 'playing';
    const STATUS_ELIMINATED    = 'eliminated';
    const STATUS_WINNER        = 'winner';
    const STATUS_DISQUALIFIED  = 'disqualified';
    const STATUS_REFUNDED      = 'refunded';

    // ── Relationships ─────────────────────────────────────────────────────────

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function matchPlayers(): HasMany
    {
        return $this->hasMany(TournamentMatchPlayer::class, 'registration_id');
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(TournamentWalletTransaction::class, 'registration_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isBot(): bool
    {
        return (bool) $this->is_bot;
    }

    public function displayName(): string
    {
        if ($this->is_bot) {
            return $this->pseudoBotPublicId();
        }

        return (string) ($this->user?->user_code ?? $this->user?->username ?? 'Unknown');
    }

    protected function pseudoBotPublicId(): string
    {
        $seed = 'bot|'.$this->id.'|'.$this->slot_number.'|'.$this->bot_name;
        $hash = abs(crc32($seed));

        return (string) (10000000 + ($hash % 90000000));
    }
}
