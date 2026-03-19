<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'entry_uuid',
        'tournament_id',
        'game_id',
        'user_id',
        'entry_no',
        'ticket_no',
        'entry_index_for_user',
        'status',
        'entry_fee',
        'wallet_hold_transaction_id',
        'wallet_capture_transaction_id',
        'wallet_refund_transaction_id',
        'joined_at',
        'checked_in_at',
        'eliminated_at',
        'completed_at',
        'meta',
    ];

    protected $casts = [
        'entry_fee' => 'decimal:2',
        'joined_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'eliminated_at' => 'datetime',
        'completed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function matchLinks(): HasMany
    {
        return $this->hasMany(TournamentMatchLink::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(TournamentEntryResult::class);
    }

    public function matchEntries(): HasMany
    {
        return $this->hasMany(TournamentMatchEntry::class);
    }

    public function activeMatchEntries(): HasMany
    {
        return $this->hasMany(TournamentMatchEntry::class)->where('status', '!=', 'completed');
    }
}
