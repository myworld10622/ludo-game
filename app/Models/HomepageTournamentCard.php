<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomepageTournamentCard extends Model
{
    protected $fillable = [
        'name', 'icon', 'description',
        'card_color', 'status_badge', 'status_text',
        'meta1_label', 'meta1_value',
        'meta2_label', 'meta2_value',
        'meta3_label', 'meta3_value',
        'tournament_id', 'sort_order', 'is_visible',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'sort_order' => 'integer',
    ];

    // ── Relationships ─────────────────────────────────────────────
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    // ── Scopes ────────────────────────────────────────────────────
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true)->orderBy('sort_order');
    }
}
