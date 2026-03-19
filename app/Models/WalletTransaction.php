<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    protected $fillable = [
        'transaction_uuid',
        'wallet_id',
        'user_id',
        'game_id',
        'tournament_id',
        'type',
        'direction',
        'status',
        'reference_type',
        'reference_id',
        'idempotency_key',
        'amount',
        'balance_before',
        'balance_after',
        'currency',
        'description',
        'meta',
        'processed_at',
    ];

    protected $appends = [
        'opening_balance',
        'closing_balance',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'balance_before' => 'decimal:4',
            'balance_after' => 'decimal:4',
            'meta' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function getOpeningBalanceAttribute(): string
    {
        return (string) $this->balance_before;
    }

    public function getClosingBalanceAttribute(): string
    {
        return (string) $this->balance_after;
    }
}
