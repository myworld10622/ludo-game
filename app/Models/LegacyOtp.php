<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegacyOtp extends Model
{
    protected $fillable = [
        'mobile',
        'type',
        'otp_code',
        'is_used',
        'expires_at',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'is_used' => 'boolean',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }
}
