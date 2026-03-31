<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_code',
        'username',
        'email',
        'mobile',
        'password',
        'referral_code',
        'referred_by_user_id',
        'is_active',
        'is_banned',
        'last_login_at',
        'email_verified_at',
        'mobile_verified_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $user) {
            if (empty($user->uuid)) {
                $user->uuid = (string) Str::uuid();
            }
            if (empty($user->user_code)) {
                do {
                    $code = str_pad((string) random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT);
                } while (self::where('user_code', $code)->exists());
                $user->user_code = $code;
            }
        });
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_banned' => 'boolean',
            'last_login_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'mobile_verified_at' => 'datetime',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referred_by_user_id');
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    public function tournamentRegistrations(): HasMany
    {
        return $this->hasMany(TournamentRegistration::class);
    }

    public function tournaments(): HasMany
    {
        return $this->hasMany(Tournament::class, 'creator_user_id');
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function supportMessages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class, 'sender_user_id');
    }

    public function primaryWallet(): HasOne
    {
        return $this->hasOne(Wallet::class)->latestOfMany('id');
    }

    public static function defaultPanelPermissions(): array
    {
        return [
            'view_panel' => true,
            'manage_tournaments' => true,
            'approve_tournaments' => false,
            'force_live' => false,
            'manage_fake_registrations' => false,
            'view_match_monitor' => true,
            'force_match_winner' => true,
        ];
    }

    public function panelPermissions(): array
    {
        $this->loadMissing('profile');

        $stored = Arr::get($this->profile?->preferences ?? [], 'panel_permissions', []);

        return array_merge(self::defaultPanelPermissions(), is_array($stored) ? $stored : []);
    }

    public function hasPanelPermission(string $permission): bool
    {
        return (bool) ($this->panelPermissions()[$permission] ?? false);
    }

    public function updatePanelPermissions(array $permissions): void
    {
        $this->loadMissing('profile');

        $profile = $this->profile ?: $this->profile()->create([]);
        $preferences = $profile->preferences ?? [];
        $preferences['panel_permissions'] = array_merge(self::defaultPanelPermissions(), $permissions);

        $profile->forceFill([
            'preferences' => $preferences,
        ])->save();
    }
}
