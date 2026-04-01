<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Tournament extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'description', 'banner_image',
        'creator_type', 'creator_user_id',
        'type', 'format', 'bracket_mode', 'status',
        'entry_fee', 'max_players', 'current_players', 'fake_registrations_count', 'players_per_match',
        'total_prize_pool', 'platform_fee_pct', 'platform_fee_amount',
        'turn_time_limit', 'match_timeout', 'disconnect_grace',
        'bot_allowed', 'max_bot_pct', 'bot_start_policy', 'min_real_players_to_start', 'bot_fill_after_seconds',
        'invite_code', 'invite_password',
        'requires_approval', 'is_approved',
        'approved_at', 'approval_note', 'rejected_at', 'rejection_reason',
        'terms_conditions',
        'play_slots',
        'registration_start_at', 'registration_end_at',
        'tournament_start_at', 'tournament_end_at',
        'completed_at', 'cancelled_at',
    ];

    protected $appends = ['can_join'];

    protected $casts = [
        'entry_fee'             => 'float',
        'total_prize_pool'      => 'float',
        'platform_fee_pct'      => 'float',
        'platform_fee_amount'   => 'float',
        'bot_allowed'           => 'boolean',
        'min_real_players_to_start' => 'integer',
        'bot_fill_after_seconds' => 'integer',
        'requires_approval'     => 'boolean',
        'is_approved'           => 'boolean',
        'play_slots'            => 'array',
        'approved_at'           => 'datetime',
        'rejected_at'           => 'datetime',
        'registration_start_at' => 'datetime',
        'registration_end_at'   => 'datetime',
        'tournament_start_at'   => 'datetime',
        'tournament_end_at'     => 'datetime',
        'completed_at'          => 'datetime',
        'cancelled_at'          => 'datetime',
    ];

    // Status Constants
    const STATUS_DRAFT               = 'draft';
    const STATUS_REGISTRATION_OPEN   = 'registration_open';
    const STATUS_REGISTRATION_CLOSED = 'registration_closed';
    const STATUS_IN_PROGRESS         = 'in_progress';
    const STATUS_COMPLETED           = 'completed';
    const STATUS_CANCELLED           = 'cancelled';

    // ── Relationships ─────────────────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    public function prizes(): HasMany
    {
        return $this->hasMany(TournamentPrize::class)->orderBy('position');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(TournamentRegistration::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class)
                    ->orderBy('round_number')
                    ->orderBy('match_number');
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(TournamentWalletTransaction::class);
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopePublicTournaments($query)
    {
        return $query->where('type', 'public');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            self::STATUS_REGISTRATION_OPEN,
            self::STATUS_IN_PROGRESS,
        ])->where('is_approved', true);
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function getCanJoinAttribute(): bool
    {
        if (! $this->is_approved) {
            return false;
        }

        // Registration open: new players can join if space available
        if ($this->status === self::STATUS_REGISTRATION_OPEN) {
            return $this->current_players < $this->max_players;
        }

        // In progress or closed: registered players can still claim their match room
        return in_array($this->status, [
            self::STATUS_IN_PROGRESS,
            self::STATUS_REGISTRATION_CLOSED,
        ]);
    }

    public function isFull(): bool
    {
        return $this->current_players >= $this->max_players;
    }

    public function isRegistrationOpen(): bool
    {
        return $this->status === self::STATUS_REGISTRATION_OPEN && ! $this->isFull();
    }

    public function hasPlaySlots(): bool
    {
        return ! empty($this->play_slots) && is_array($this->play_slots);
    }

    public function activePlaySlotIndex(?\Carbon\CarbonInterface $now = null): ?int
    {
        $now ??= now();

        foreach ($this->normalizedPlaySlots() as $slot) {
            if ($slot['start_at'] && $slot['end_at'] && $now->between($slot['start_at'], $slot['end_at'])) {
                return $slot['index'];
            }
        }

        return null;
    }

    public function normalizedPlaySlots(): array
    {
        return collect($this->play_slots ?? [])
            ->map(function ($slot, $index) {
                $startAt = data_get($slot, 'start_at');
                $endAt = data_get($slot, 'end_at');

                return [
                    'index' => (int) $index + 1,
                    'label' => data_get($slot, 'label') ?: 'Slot ' . ((int) $index + 1),
                    'start_at' => $startAt ? now()->parse($startAt) : null,
                    'end_at' => $endAt ? now()->parse($endAt) : null,
                ];
            })
            ->filter(fn ($slot) => $slot['start_at'] && $slot['end_at'])
            ->values()
            ->all();
    }

    public function maxBotsAllowed(): int
    {
        return (int) ceil($this->max_players * ($this->max_bot_pct / 100));
    }

    public function currentBotCount(): int
    {
        return $this->registrations()->where('is_bot', true)->count();
    }

    public function canAddBot(): bool
    {
        return $this->bot_allowed && $this->currentBotCount() < $this->maxBotsAllowed();
    }

    public function resolveBotStartPolicy(): string
    {
        if (! $this->bot_allowed) {
            return 'disabled';
        }

        $policy = (string) ($this->bot_start_policy ?: 'hybrid');

        return in_array($policy, ['disabled', 'fill_missing', 'replace_offline', 'hybrid'], true)
            ? $policy
            : 'hybrid';
    }

    public function resolveMinRealPlayersToStart(): int
    {
        $matchSize = in_array((int) $this->players_per_match, [2, 4], true)
            ? (int) $this->players_per_match
            : 4;

        $minRealPlayers = (int) ($this->min_real_players_to_start ?: 1);

        return max(1, min($matchSize, $minRealPlayers));
    }

    public function resolveBotFillAfterSeconds(): int
    {
        return max(0, min(300, (int) ($this->bot_fill_after_seconds ?? 8)));
    }

    public function tournamentBotsCanFillMissingSeats(): bool
    {
        return in_array($this->resolveBotStartPolicy(), ['fill_missing', 'hybrid'], true);
    }

    public function tournamentBotsCanReplaceOfflinePlayers(): bool
    {
        return in_array($this->resolveBotStartPolicy(), ['replace_offline', 'hybrid'], true);
    }

    /**
     * Recalculate prize pool = 80% of total entry fees.
     * Updates prize_amount on each prize slot.
     */
    public function recalculatePrizePool(): void
    {
        $totalEntryPool            = $this->current_players * $this->entry_fee;
        $platformFee               = $totalEntryPool * ($this->platform_fee_pct / 100);
        $this->total_prize_pool    = $totalEntryPool - $platformFee;
        $this->platform_fee_amount = $platformFee;
        $this->save();

        foreach ($this->prizes as $prize) {
            $prize->prize_amount = $this->total_prize_pool * ($prize->prize_pct / 100);
            $prize->save();
        }
    }

    /**
     * Generate a unique 6-char invite code for private tournaments.
     */
    public static function generateInviteCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (self::where('invite_code', $code)->exists());

        return $code;
    }
}
