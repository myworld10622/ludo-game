<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRecoveryChannel extends Model
{
    protected $fillable = [
        'user_id',
        'channel_type',
        'channel_value',
        'is_verified',
        'verified_at',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'is_primary' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
