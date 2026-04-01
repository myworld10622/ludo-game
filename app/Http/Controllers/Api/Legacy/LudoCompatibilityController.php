<?php

namespace App\Http\Controllers\Api\Legacy;

use App\Http\Controllers\Controller;
use App\Models\ClassicLudoTable;
use App\Models\Game;
use App\Models\GameRoom;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class LudoCompatibilityController extends Controller
{
    public function getTableMaster(Request $request): JsonResponse
    {
        $user = $this->resolveLegacyUser(
            $request->input('user_id') ?: $request->input('id'),
            $request->input('token')
        );

        if (! $user) {
            return response()->json([
                'message' => 'Invalid User',
                'table_data' => [],
                'code' => 411,
            ]);
        }

        $playerCount = (int) $request->input('no_of_players', 0);
        if ($playerCount !== 0 && ! in_array($playerCount, [2, 4], true)) {
            return response()->json([
                'message' => 'Invalid No. Of Players',
                'table_data' => [],
                'code' => 406,
            ]);
        }

        $ludoGame = Game::query()->where('slug', 'ludo')->first();

        if (! $ludoGame) {
            return response()->json([
                'message' => 'Ludo game not configured.',
                'table_data' => [],
                'code' => 404,
            ]);
        }

        $this->ensureDefaultTables($ludoGame);

        $tables = ClassicLudoTable::query()
            ->where('game_id', $ludoGame->id)
            ->where('is_active', true)
            ->when($playerCount > 0, fn ($query) => $query->where('player_count', $playerCount))
            ->orderBy('player_count')
            ->orderBy('sort_order')
            ->orderBy('entry_fee')
            ->get();

        $occupancy = GameRoom::query()
            ->where('game_id', $ludoGame->id)
            ->where('room_type', 'public')
            ->where('play_mode', 'cash')
            ->whereIn('status', ['waiting', 'starting', 'live', 'active', 'in_progress', 'playing'])
            ->selectRaw('CAST(entry_fee as DECIMAL(16,4)) as entry_fee_key, max_players')
            ->selectRaw('SUM(current_players) as occupied_seats')
            ->groupBy('entry_fee_key', 'max_players')
            ->get()
            ->keyBy(fn ($row) => number_format((float) $row->entry_fee_key, 4, '.', '').'|'.(int) $row->max_players);

        $payload = $tables->values()->map(function (ClassicLudoTable $table) use ($occupancy) {
            $key = number_format((float) $table->entry_fee, 4, '.', '').'|'.(int) $table->player_count;
            $onlineMembers = (int) ($occupancy->get($key)->occupied_seats ?? 0);

            return [
                'id' => (string) $table->id,
                'room_id' => '',
                'boot_value' => number_format((float) $table->entry_fee, 2, '.', ''),
                'maximum_blind' => '',
                'chaal_limit' => '',
                'pot_limit' => '',
                'added_date' => optional($table->created_at)?->toDateTimeString() ?? '',
                'updated_date' => optional($table->updated_at)?->toDateTimeString() ?? '',
                'isDeleted' => '0',
                'online_members' => (string) $onlineMembers,
                'no_of_players' => (string) $table->player_count,
            ];
        })->all();

        return response()->json([
            'message' => 'Success',
            'table_data' => $payload,
            'code' => 200,
        ]);
    }

    public function getTableMasterBachpan(Request $request): JsonResponse
    {
        $request->merge([
            'no_of_players' => $request->input('no_of_players', 4),
        ]);

        return $this->getTableMaster($request);
    }

    protected function resolveLegacyUser($id, $token): ?User
    {
        if (! $id || ! $token) {
            return null;
        }

        $accessToken = PersonalAccessToken::findToken((string) $token);

        if (! $accessToken) {
            return null;
        }

        $user = User::query()->find($accessToken->tokenable_id);

        if (! $user) {
            return null;
        }

        $publicId = (string) $id;

        if ($publicId !== '' && $publicId !== (string) $user->id && $publicId !== (string) $user->user_code) {
            return null;
        }

        return $user;
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
