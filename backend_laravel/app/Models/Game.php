<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    protected $fillable = [
        'code',
        'name',
        'slug',
        'description',
        'is_active',
        'is_visible',
        'tournaments_enabled',
        'sort_order',
        'launch_type',
        'client_route',
        'socket_namespace',
        'icon_url',
        'banner_url',
        'metadata',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_visible' => 'boolean',
            'tournaments_enabled' => 'boolean',
            'metadata' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function settings(): HasMany
    {
        return $this->hasMany(GameSetting::class);
    }

    public function tournaments(): HasMany
    {
        return $this->hasMany(Tournament::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(GameRoom::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class);
    }

    public function classicLudoTables(): HasMany
    {
        return $this->hasMany(ClassicLudoTable::class);
    }
}
