<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentMatchLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'tournament_entry_id',
        'game_match_id',
        'external_match_uuid',
        'round_no',
        'table_no',
        'status',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(TournamentEntry::class, 'tournament_entry_id');
    }

    public function gameMatch(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class);
    }
}
