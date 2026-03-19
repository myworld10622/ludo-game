<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminActionLog extends Model
{
    protected $fillable = [
        'admin_user_id',
        'action',
        'target_type',
        'target_id',
        'route_name',
        'method',
        'ip_address',
        'user_agent',
        'payload',
        'meta',
        'performed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'meta' => 'array',
            'performed_at' => 'datetime',
        ];
    }

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class);
    }
}
