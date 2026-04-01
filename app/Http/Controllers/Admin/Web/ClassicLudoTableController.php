<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use App\Models\ClassicLudoTable;
use App\Models\Game;
use App\Models\GameRoom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ClassicLudoTableController extends Controller
{
    public function index(): View
    {
        $ludoGame = Game::query()->where('slug', 'ludo')->firstOrFail();
        $this->ensureDefaultTables($ludoGame);

        $tables = ClassicLudoTable::query()
            ->where('game_id', $ludoGame->id)
            ->orderBy('player_count')
            ->orderBy('sort_order')
            ->orderBy('entry_fee')
            ->get();

        $roomStats = GameRoom::query()
            ->where('game_id', $ludoGame->id)
            ->where('room_type', 'public')
            ->where('play_mode', 'cash')
            ->selectRaw('CAST(entry_fee as DECIMAL(16,4)) as entry_fee_key, max_players')
            ->selectRaw('COUNT(*) as total_rooms')
            ->selectRaw("SUM(CASE WHEN status IN ('waiting', 'starting') THEN 1 ELSE 0 END) as waiting_rooms")
            ->selectRaw("SUM(CASE WHEN status IN ('live', 'active', 'in_progress', 'playing') THEN 1 ELSE 0 END) as live_rooms")
            ->selectRaw('SUM(current_players) as current_players_sum')
            ->selectRaw('SUM(current_real_players) as current_real_players_sum')
            ->selectRaw('SUM(current_bot_players) as current_bot_players_sum')
            ->groupBy('entry_fee_key', 'max_players')
            ->get()
            ->keyBy(fn ($row) => $this->makeStatKey((float) $row->entry_fee_key, (int) $row->max_players));

        $tableSummaries = $tables->map(function (ClassicLudoTable $table) use ($roomStats) {
            $stats = $roomStats->get($this->makeStatKey((float) $table->entry_fee, (int) $table->player_count));

            return [
                'table' => $table,
                'total_rooms' => (int) ($stats->total_rooms ?? 0),
                'waiting_rooms' => (int) ($stats->waiting_rooms ?? 0),
                'live_rooms' => (int) ($stats->live_rooms ?? 0),
                'current_players' => (int) ($stats->current_players_sum ?? 0),
                'current_real_players' => (int) ($stats->current_real_players_sum ?? 0),
                'current_bot_players' => (int) ($stats->current_bot_players_sum ?? 0),
                'estimated_prize_pool' => round((float) $table->entry_fee * (int) $table->player_count, 2),
            ];
        });

        $recentRooms = GameRoom::query()
            ->with([
                'players.user.profile',
                'matches' => fn ($query) => $query->latest('id')->limit(1),
            ])
            ->where('game_id', $ludoGame->id)
            ->where('room_type', 'public')
            ->where('play_mode', 'cash')
            ->latest('id')
            ->limit(18)
            ->get();

        $stats = [
            'total_tables' => $tables->count(),
            'active_tables' => $tables->where('is_active', true)->count(),
            'two_player_tables' => $tables->where('player_count', 2)->where('is_active', true)->count(),
            'four_player_tables' => $tables->where('player_count', 4)->where('is_active', true)->count(),
            'waiting_rooms' => (int) $recentRooms->whereIn('status', ['waiting', 'starting'])->count(),
            'live_rooms' => (int) $recentRooms->whereIn('status', ['live', 'active', 'in_progress', 'playing'])->count(),
        ];

        return view('admin.games.ludo-tables.index', [
            'ludoGame' => $ludoGame,
            'tableSummaries' => $tableSummaries,
            'recentRooms' => $recentRooms,
            'stats' => $stats,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $ludoGame = Game::query()->where('slug', 'ludo')->firstOrFail();
        $validated = $this->validatePayload($request);

        ClassicLudoTable::query()->updateOrCreate(
            [
                'game_id' => $ludoGame->id,
                'player_count' => (int) $validated['player_count'],
                'entry_fee' => number_format((float) $validated['entry_fee'], 4, '.', ''),
            ],
            [
                'sort_order' => (int) $validated['sort_order'],
                'is_active' => (bool) ($validated['is_active'] ?? false),
                'notes' => $validated['notes'] ?? null,
            ]
        );

        return back()->with('status', 'Classic Ludo fee table added successfully.');
    }

    public function update(Request $request, ClassicLudoTable $classicLudoTable): RedirectResponse
    {
        $validated = $this->validatePayload($request, $classicLudoTable);

        $classicLudoTable->update([
            'player_count' => (int) $validated['player_count'],
            'entry_fee' => number_format((float) $validated['entry_fee'], 4, '.', ''),
            'sort_order' => (int) $validated['sort_order'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('status', 'Classic Ludo fee table updated successfully.');
    }

    public function destroy(ClassicLudoTable $classicLudoTable): RedirectResponse
    {
        $hasActiveRooms = GameRoom::query()
            ->where('game_id', $classicLudoTable->game_id)
            ->where('room_type', 'public')
            ->where('play_mode', 'cash')
            ->where('max_players', $classicLudoTable->player_count)
            ->where('entry_fee', $classicLudoTable->entry_fee)
            ->whereIn('status', ['waiting', 'starting', 'live', 'active', 'in_progress', 'playing'])
            ->exists();

        if ($hasActiveRooms) {
            return back()->withErrors([
                'classic_ludo_table' => 'Active room chal raha hai. Pehle room complete hone do ya table ko disable karo.',
            ]);
        }

        $classicLudoTable->delete();

        return back()->with('status', 'Classic Ludo fee table removed successfully.');
    }

    protected function validatePayload(Request $request, ?ClassicLudoTable $classicLudoTable = null): array
    {
        $validated = $request->validate([
            'player_count' => ['required', 'integer', Rule::in([2, 4])],
            'entry_fee' => ['required', 'numeric', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $duplicateExists = ClassicLudoTable::query()
            ->where('game_id', $classicLudoTable?->game_id ?: Game::query()->where('slug', 'ludo')->value('id'))
            ->where('player_count', (int) $validated['player_count'])
            ->where('entry_fee', number_format((float) $validated['entry_fee'], 4, '.', ''))
            ->when($classicLudoTable, fn ($query) => $query->whereKeyNot($classicLudoTable->id))
            ->exists();

        if ($duplicateExists) {
            throw ValidationException::withMessages([
                'classic_ludo_table' => 'Same player count aur same entry fee wali table already bani hui hai.',
            ]);
        }

        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }

    protected function makeStatKey(float $entryFee, int $playerCount): string
    {
        return number_format($entryFee, 4, '.', '').'|'.$playerCount;
    }

    protected function ensureDefaultTables(Game $ludoGame): void
    {
        $defaultFees = [20, 40, 100, 200, 300, 400, 500, 600, 800, 1000];

        foreach ([2, 4] as $playerCount) {
            foreach ($defaultFees as $index => $fee) {
                ClassicLudoTable::query()->firstOrCreate(
                    [
                        'game_id' => $ludoGame->id,
                        'player_count' => $playerCount,
                        'entry_fee' => number_format((float) $fee, 4, '.', ''),
                    ],
                    [
                        'sort_order' => $index + 1,
                        'is_active' => true,
                        'notes' => 'Legacy classic lobby default table',
                    ]
                );
            }
        }
    }
}
