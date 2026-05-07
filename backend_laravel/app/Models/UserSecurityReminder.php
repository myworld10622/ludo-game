<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSecurityReminder extends Model
{
    protected $fillable = [
        'user_id',
        'last_shown_at',
        'dismissed_until',
        'is_completed',
    ];

    protected function casts(): array
    {
        return [
            'last_shown_at' => 'datetime',
            'dismissed_until' => 'datetime',
            'is_completed' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
