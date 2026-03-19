<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'event',
        'actor_type',
        'actor_id',
        'auditable_type',
        'auditable_id',
        'source',
        'ip_address',
        'user_agent',
        'before',
        'after',
        'meta',
        'created_event_at',
    ];

    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
            'meta' => 'array',
            'created_event_at' => 'datetime',
        ];
    }
}
