<?php

namespace App\Services\Tournament;

use App\Models\Tournament;
use App\Models\TournamentEntry;
use App\Models\User;
use App\Services\Wallet\WalletService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TournamentService
{
    public function __construct(
        protected WalletService $walletService
    ) {
    }

    public function adminList(int $perPage = 20): LengthAwarePaginator
    {
        return Tournament::query()
            ->with(['game', 'prizes'])
            ->latest()
            ->paginate($perPage);
    }

    public function publicList(?int $gameId = null, int $perPage = 20): LengthAwarePaginator
    {
        return Tournament::query()
            ->with(['game', 'prizes'])
            ->whereIn('status', ['published', 'live'])
            ->when($gameId, fn ($query) => $query->where('game_id', $gameId))
            ->orderBy('starts_at')
            ->paginate($perPage);
    }

    public function create(array $payload): Tournament
    {
        return DB::transaction(function () use ($payload) {
            $tournament = Tournament::query()->create($this->normalizePayload($payload));
            $this->syncPrizeSlabs($tournament, $payload['prize_slabs'] ?? []);

            return $tournament->load(['game', 'prizes']);
        });
    }

    public function update(Tournament $tournament, array $payload): Tournament
    {
        return DB::transaction(function () use ($tournament, $payload) {
            $tournament->fill($this->normalizePayload($payload, $tournament))->save();

            if (array_key_exists('prize_slabs', $payload)) {
                $tournament->prizes()->delete();
                $this->syncPrizeSlabs($tournament, $payload['prize_slabs']);
            }

            return $tournament->load(['game', 'prizes']);
        });
    }

    public function detail(Tournament $tournament): Tournament
    {
        return $tournament->load(['game', 'prizes']);
    }

    public function myEntries(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return TournamentEntry::query()
            ->with(['tournament.game', 'tournament.prizes'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate($perPage);
    }

    public function leaderboard(Tournament $tournament, int $limit = 100): Collection
    {
        return TournamentEntry::query()
            ->with('user')
            ->where('tournament_id', $tournament->id)
            ->orderByRaw('CASE WHEN final_rank IS NULL THEN 1 ELSE 0 END')
            ->orderBy('final_rank')
            ->orderByDesc('prize_amount')
            ->orderBy('entry_no')
            ->limit($limit)
            ->get();
    }

    public function join(User $user, Tournament $tournament): TournamentEntry
    {
        return DB::transaction(function () use ($user, $tournament) {
            $tournament = Tournament::query()->lockForUpdate()->findOrFail($tournament->id);

            $this->assertJoinable($user, $tournament);

            $entryNo = (int) TournamentEntry::query()
                ->where('tournament_id', $tournament->id)
                ->where('user_id', $user->id)
                ->max('entry_no') + 1;

            $walletTransaction = $this->walletService->debitForTournamentEntry($user, $tournament, [
                'entry_no' => $entryNo,
            ]);

            return TournamentEntry::query()->create([
                'entry_uuid' => (string) Str::uuid(),
                'tournament_id' => $tournament->id,
                'game_id' => $tournament->game_id,
                'user_id' => $user->id,
                'wallet_transaction_id' => $walletTransaction->id,
                'entry_no' => $entryNo,
                'status' => 'registered',
                'entry_fee' => $tournament->entry_fee,
                'prize_amount' => 0,
            ])->load(['tournament.game', 'tournament.prizes', 'user']);
        });
    }

    protected function assertJoinable(User $user, Tournament $tournament): void
    {
        if (! in_array($tournament->status, ['published', 'live'], true)) {
            throw new HttpException(422, 'Tournament is not open for joining.');
        }

        $now = now();

        if ($tournament->registration_starts_at && $now->lt($tournament->registration_starts_at)) {
            throw new HttpException(422, 'Tournament registration has not started yet.');
        }

        if ($tournament->registration_ends_at && $now->gt($tournament->registration_ends_at)) {
            throw new HttpException(422, 'Tournament registration is closed.');
        }

        $userEntriesCount = TournamentEntry::query()
            ->where('tournament_id', $tournament->id)
            ->where('user_id', $user->id)
            ->count();

        if ($userEntriesCount >= $tournament->max_entries_per_user) {
            throw new HttpException(422, 'Maximum tournament entries per user reached.');
        }

        if ($tournament->max_total_entries) {
            $totalEntries = TournamentEntry::query()
                ->where('tournament_id', $tournament->id)
                ->count();

            if ($totalEntries >= $tournament->max_total_entries) {
                throw new HttpException(422, 'Tournament entry limit has been reached.');
            }
        }
    }

    protected function syncPrizeSlabs(Tournament $tournament, array $prizeSlabs): void
    {
        foreach ($prizeSlabs as $slab) {
            $tournament->prizes()->create([
                'rank_from' => $slab['rank_from'],
                'rank_to' => $slab['rank_to'],
                'prize_type' => $slab['prize_type'],
                'prize_amount' => $slab['prize_amount'],
                'currency' => $slab['currency'] ?? $tournament->currency,
            ]);
        }
    }

    protected function normalizePayload(array $payload, ?Tournament $tournament = null): array
    {
        $current = $tournament?->toArray() ?? [];
        $name = $payload['name'] ?? $current['name'] ?? 'Tournament';
        $code = $payload['code'] ?? $current['code'] ?? null;
        $slug = $payload['slug'] ?? $current['slug'] ?? null;
        $ticketPrefix = $payload['ticket_prefix'] ?? $current['ticket_prefix'] ?? null;

        if (blank($code)) {
            $code = $this->generateUniqueCode($name);
        }

        if (blank($slug)) {
            $slug = $this->generateUniqueSlug($name);
        }

        if (blank($ticketPrefix)) {
            $ticketPrefix = $this->generateTicketPrefix($code);
        }

        return [
            'game_id' => $payload['game_id'] ?? $current['game_id'],
            'uuid' => $payload['uuid'] ?? ($current['uuid'] ?? (string) Str::uuid()),
            'code' => $code,
            'name' => $name,
            'slug' => $slug,
            'type' => $payload['tournament_type'] ?? $payload['type'] ?? ($current['type'] ?? $current['tournament_type'] ?? 'standard'),
            'status' => $payload['status'] ?? $current['status'],
            'max_entries_per_user' => $payload['max_entries_per_user'] ?? $current['max_entries_per_user'],
            'max_total_entries' => $payload['max_total_entries'] ?? ($current['max_total_entries'] ?? null),
            'min_total_entries' => $payload['min_total_entries'] ?? $payload['min_players'] ?? ($current['min_total_entries'] ?? $current['min_players'] ?? 2),
            'match_size' => $payload['match_size'] ?? $payload['max_players'] ?? ($current['match_size'] ?? $current['max_players'] ?? 4),
            'advance_count' => $payload['advance_count'] ?? ($current['advance_count'] ?? 1),
            'bracket_size' => $payload['bracket_size'] ?? ($current['bracket_size'] ?? null),
            'bye_count' => $payload['bye_count'] ?? ($current['bye_count'] ?? 0),
            'seeding_strategy' => $payload['seeding_strategy'] ?? ($current['seeding_strategy'] ?? 'random'),
            'bot_fill_policy' => $payload['bot_fill_policy'] ?? ($current['bot_fill_policy'] ?? 'fill_after_timeout'),
            'entry_fee' => $payload['entry_fee'] ?? $current['entry_fee'],
            'currency' => $payload['currency'] ?? ($current['currency'] ?? 'INR'),
            'platform_fee' => $payload['platform_fee'] ?? ($current['platform_fee'] ?? 0),
            'allow_multiple_entries' => (($payload['max_entries_per_user'] ?? $current['max_entries_per_user'] ?? 1) > 1),
            'ticket_prefix' => $ticketPrefix,
            'entry_open_at' => $payload['entry_open_at'] ?? $payload['registration_starts_at'] ?? ($current['entry_open_at'] ?? $current['registration_starts_at'] ?? null),
            'entry_close_at' => $payload['entry_close_at'] ?? $payload['registration_ends_at'] ?? ($current['entry_close_at'] ?? $current['registration_ends_at'] ?? null),
            'start_at' => $payload['start_at'] ?? $payload['starts_at'] ?? ($current['start_at'] ?? $current['starts_at'] ?? null),
            'end_at' => $payload['end_at'] ?? $payload['ends_at'] ?? ($current['end_at'] ?? $current['ends_at'] ?? null),
            'rules' => $payload['rules'] ?? $payload['settings'] ?? ($current['rules'] ?? $current['settings'] ?? null),
            'meta' => $payload['meta'] ?? $payload['metadata'] ?? ($current['meta'] ?? $current['metadata'] ?? null),
        ];
    }

    protected function generateUniqueCode(string $name): string
    {
        $base = Str::upper(Str::substr(preg_replace('/[^A-Za-z0-9]+/', '-', $name) ?: 'TRN', 0, 24));
        $base = trim($base, '-');

        do {
            $candidate = sprintf('%s-%s', $base ?: 'TRN', Str::upper(Str::random(6)));
        } while (Tournament::query()->where('code', $candidate)->exists());

        return $candidate;
    }

    protected function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'tournament';
        $candidate = $base;
        $counter = 2;

        while (Tournament::query()->where('slug', $candidate)->exists()) {
            $candidate = $base . '-' . $counter;
            $counter++;
        }

        return $candidate;
    }

    protected function generateTicketPrefix(string $code): string
    {
        $prefix = Str::upper(Str::substr(preg_replace('/[^A-Za-z0-9]+/', '', $code) ?: 'TRN', 0, 8));

        return $prefix !== '' ? $prefix : 'TRN';
    }
}
