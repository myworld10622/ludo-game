<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'game_id',
        'slug',
        'name',
        'code',
        'type',
        'status',
        'currency',
        'entry_fee',
        'platform_fee',
        'allow_multiple_entries',
        'max_entries_per_user',
        'min_total_entries',
        'max_total_entries',
        'match_size',
        'advance_count',
        'bracket_size',
        'bye_count',
        'seeding_strategy',
        'bot_fill_policy',
        'ticket_prefix',
        'next_entry_no',
        'current_total_entries',
        'current_active_entries',
        'entry_open_at',
        'entry_close_at',
        'start_at',
        'end_at',
        'completed_at',
        'cancelled_at',
        'rules',
        'meta',
        'created_by_admin_id',
        'updated_by_admin_id',
    ];

    protected $casts = [
        'allow_multiple_entries' => 'boolean',
        'entry_fee' => 'decimal:2',
        'platform_fee' => 'decimal:4',
        'match_size' => 'integer',
        'advance_count' => 'integer',
        'bracket_size' => 'integer',
        'bye_count' => 'integer',
        'rules' => 'array',
        'meta' => 'array',
        'entry_open_at' => 'datetime',
        'entry_close_at' => 'datetime',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function prizes(): HasMany
    {
        return $this->hasMany(TournamentPrize::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(TournamentEntry::class);
    }

    public function matchLinks(): HasMany
    {
        return $this->hasMany(TournamentMatchLink::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(TournamentEntryResult::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class);
    }
}
