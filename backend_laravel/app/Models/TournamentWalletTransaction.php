<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentWalletTransaction extends Model
{
    protected $fillable = [
        'tournament_id',
        'user_id',
        'type',
        'amount',
        'status',
        'reference_id',
        'description',
        'registration_id',
        'match_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    const TYPE_ENTRY_FEE         = 'entry_fee';
    const TYPE_PRIZE_CREDIT      = 'prize_credit';
    const TYPE_REFUND            = 'refund';
    const TYPE_PLATFORM_FEE      = 'platform_fee';
    const TYPE_CREATION_DEPOSIT  = 'creation_deposit';

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(TournamentRegistration::class);
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(TournamentMatch::class);
    }
}
