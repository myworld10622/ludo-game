<?php

namespace App\Http\Controllers\Api\Legacy;

use App\Http\Controllers\Controller;
use App\Models\ClassicLudoTable;
use App\Models\Game;
use App\Models\GameRoom;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

    public function status(Request $request): JsonResponse
    {
        $user = $this->resolveLegacyUser(
            $request->input('user_id') ?: $request->input('id'),
            $request->input('token')
        );

        if (! $user) {
            return response()->json([
                'message' => 'Invalid User',
                'code' => 405,
            ]);
        }

        $legacyUser = $this->resolveLegacyDbUser($user);

        if (! $legacyUser || empty($legacyUser->ludo_table_id)) {
            return response()->json([
                'message' => 'You Are Not On Table',
                'code' => 403,
            ]);
        }

        $tableId = (int) $legacyUser->ludo_table_id;
        $tableUsers = $this->legacyTableUsers($tableId);

        $tableSlots = [];
        for ($i = 0; $i < 4; $i++) {
            $tableSlots[$i] = (object) [
                'id' => 0,
                'table_id' => 0,
                'user_id' => 0,
                'seat_position' => $i + 1,
                'added_date' => 0,
                'updated_date' => 0,
                'isDeleted' => 0,
                'name' => 0,
                'mobile' => 0,
                'profile_pic' => 0,
                'wallet' => 0,
            ];
        }

        foreach ($tableUsers as $userRow) {
            $slotIndex = max(0, ((int) $userRow->seat_position) - 1);
            $tableSlots[$slotIndex] = $userRow;
        }

        $table = $this->legacyTableRow('tbl_ludo_table', $tableId);

        if (! $table) {
            return response()->json([
                'message' => 'Invalid Table',
                'code' => 404,
            ]);
        }

        $activeGame = $this->legacyActiveGame($tableId);
        $activeGameId = $activeGame?->id ?? 0;

        $gameId = (int) ($request->input('game_id') ?: $activeGameId);
        if ($gameId <= 0) {
            return response()->json([
                'message' => 'Invalid Parameter',
                'code' => 406,
            ]);
        }

        $game = $this->legacyGame($gameId);
        if (! $game) {
            return response()->json([
                'message' => 'Invalid Game',
                'code' => 406,
            ]);
        }

        $gameLog = $this->legacyGameLog($gameId, 1)->first();
        if (! $gameLog) {
            return response()->json([
                'message' => 'Invalid Game Log',
                'code' => 406,
            ]);
        }

        $chaal = $this->legacyChaal($gameId, $gameLog, $game);
        $dice = (int) ($gameLog->action == 2 ? $gameLog->step : 0);

        $payload = [
            'table_users' => $tableSlots,
            'table_detail' => $table,
            'active_game_id' => $activeGameId,
            'game_status' => $activeGameId > 0 ? 1 : 0,
            'table_amount' => (float) ($table->boot_value ?? 0),
            'game_log' => [$gameLog],
            'all_users' => $tableUsers,
            'dice' => $dice,
            'game_users' => $this->legacyGameOnlyUsers($gameId),
            'chaal' => $chaal,
            'game_amount' => $game->amount ?? 0,
            'all_steps' => $this->legacyGameCards($gameId),
            'message' => 'Success',
            'code' => 200,
        ];

        $playerCount = (int) ($table->no_of_players ?? 0);
        if ($playerCount === 2 && (int) $game->winner_id > 0) {
            $payload['chaal'] = 0;
            $payload['message'] = 'Game Completed';
            $payload['game_status'] = 2;
            $payload['winner_user_id'] = (int) $game->winner_id;
        } elseif ($playerCount === 3 && (int) $game->winner_id_2 > 0) {
            $payload['chaal'] = 0;
            $payload['message'] = 'Game Completed';
            $payload['game_status'] = 2;
            $payload['winner_user_id'] = (int) $game->winner_id;
            $payload['winner_2_user_id'] = (int) $game->winner_id_2;
        } elseif ($playerCount === 4 && (int) $game->winner_id_3 > 0) {
            $payload['chaal'] = 0;
            $payload['message'] = 'Game Completed';
            $payload['game_status'] = 2;
            $payload['winner_user_id'] = (int) $game->winner_id;
            $payload['winner_2_user_id'] = (int) $game->winner_id_2;
            $payload['winner_3_user_id'] = (int) $game->winner_id_3;
        }

        return response()->json($payload);
    }

    public function userGameHistory(Request $request): JsonResponse
    {
        $user = $this->resolveLegacyUser(
            $request->input('user_id') ?: $request->input('id'),
            $request->input('token')
        );

        if (! $user) {
            return response()->json([
                'message' => 'Invalid User',
                'code' => 405,
            ]);
        }

        $legacyUser = $this->resolveLegacyDbUser($user);

        if (! $legacyUser || empty($legacyUser->ludo_table_id)) {
            return response()->json([
                'message' => 'You Are Not On Table',
                'code' => 406,
            ]);
        }

        $lastGame = $this->legacyLastCompletedGame((int) $legacyUser->ludo_table_id);
        if (! $lastGame) {
            return response()->json([
                'message' => 'This is First Game',
                'code' => 406,
            ]);
        }

        $gameUsers = $this->legacyGameOnlyUsers($lastGame->id);
        $gameUsersCards = [];

        foreach ($gameUsers as $index => $gameUser) {
            $declareLog = $this->legacyGameLog($lastGame->id, 1, $gameUser->user_id)->first();
            $jsonCards = $this->legacyGameLogJson($lastGame->id, $gameUser->user_id);

            $gameUser->win = ((int) $lastGame->winner_id === (int) $gameUser->user_id)
                ? ($lastGame->user_winning_amt ?? 0)
                : ($declareLog->amount ?? 0);
            $gameUser->result = $declareLog->action ?? 0;
            $gameUser->score = $declareLog->points ?? 0;
            $gameUser->cards = $jsonCards ? json_decode($jsonCards) : [];

            $gameUsersCards[$index] = [
                'user' => $gameUser,
            ];
        }

        return response()->json([
            'game_users_cards' => $gameUsersCards,
            'message' => 'Success',
            'code' => 200,
        ]);
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

    protected function resolveLegacyDbUser(User $user): ?object
    {
        if (! $this->legacyTableExists('tbl_users')) {
            return null;
        }

        $query = DB::table('tbl_users');
        $hasCriteria = false;

        if (! empty($user->mobile)) {
            $query->orWhere('mobile', $user->mobile);
            $hasCriteria = true;
        }

        if (! empty($user->email)) {
            $query->orWhere('email', $user->email);
            $hasCriteria = true;
        }

        if (! $hasCriteria) {
            if (is_numeric($user->user_code ?? null)) {
                return DB::table('tbl_users')
                    ->where('id', (int) $user->user_code)
                    ->first();
            }

            return null;
        }

        return $query->orderByDesc('id')->first();
    }

    protected function legacyTableUsers(int $tableId): array
    {
        if (! $this->legacyTableExists('tbl_ludo_table_user') || ! $this->legacyTableExists('tbl_users')) {
            return [];
        }

        return DB::table('tbl_ludo_table_user')
            ->select(
                'tbl_ludo_table_user.*',
                'tbl_users.name',
                'tbl_users.mobile',
                'tbl_users.profile_pic',
                'tbl_users.wallet',
                'tbl_users.user_type',
                'tbl_ludo_table.no_of_players',
                'tbl_ludo_table.boot_value'
            )
            ->join('tbl_users', 'tbl_ludo_table_user.user_id', '=', 'tbl_users.id')
            ->join('tbl_ludo_table', 'tbl_ludo_table_user.ludo_table_id', '=', 'tbl_ludo_table.id')
            ->where('tbl_ludo_table_user.isDeleted', 0)
            ->where('tbl_ludo_table_user.ludo_table_id', $tableId)
            ->orderBy('tbl_ludo_table_user.seat_position')
            ->get()
            ->all();
    }

    protected function legacyTableRow(string $table, int $id): ?object
    {
        if (! $this->legacyTableExists($table)) {
            return null;
        }

        return DB::table($table)
            ->where('isDeleted', 0)
            ->where('id', $id)
            ->first();
    }

    protected function legacyActiveGame(int $tableId): ?object
    {
        if (! $this->legacyTableExists('tbl_ludo')) {
            return null;
        }

        return DB::table('tbl_ludo')
            ->where('isDeleted', 0)
            ->where('winner_id', 0)
            ->where('ludo_table_id', $tableId)
            ->orderByDesc('id')
            ->first();
    }

    protected function legacyLastCompletedGame(int $tableId): ?object
    {
        if (! $this->legacyTableExists('tbl_ludo')) {
            return null;
        }

        return DB::table('tbl_ludo')
            ->where('isDeleted', 0)
            ->where('winner_id', '!=', 0)
            ->where('ludo_table_id', $tableId)
            ->orderByDesc('id')
            ->first();
    }

    protected function legacyGame(int $gameId): ?object
    {
        if (! $this->legacyTableExists('tbl_ludo')) {
            return null;
        }

        return DB::table('tbl_ludo')
            ->where('isDeleted', 0)
            ->where('id', $gameId)
            ->first();
    }

    protected function legacyGameLog(int $gameId, int $limit = 0, int $userId = 0)
    {
        if (! $this->legacyTableExists('tbl_ludo_log')) {
            return collect();
        }

        $query = DB::table('tbl_ludo_log')
            ->where('game_id', $gameId)
            ->orderByDesc('id');

        if ($userId > 0) {
            $query->where('user_id', $userId);
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }

    protected function legacyGameLogJson(int $gameId, int $userId): ?string
    {
        if (! $this->legacyTableExists('tbl_ludo_log')) {
            return null;
        }

        $row = DB::table('tbl_ludo_log')
            ->where('game_id', $gameId)
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->first();

        return $row?->json;
    }

    protected function legacyGameOnlyUsers(int $gameId): array
    {
        if (! $this->legacyTableExists('tbl_ludo_card') || ! $this->legacyTableExists('tbl_users')) {
            return [];
        }

        return DB::table('tbl_ludo_card')
            ->select('tbl_ludo_card.user_id', 'tbl_ludo_card.packed', 'tbl_users.name', 'tbl_users.profile_pic')
            ->join('tbl_users', 'tbl_users.id', '=', 'tbl_ludo_card.user_id')
            ->where('tbl_ludo_card.game_id', $gameId)
            ->groupBy('tbl_ludo_card.user_id')
            ->get()
            ->all();
    }

    protected function legacyGameCards(int $gameId): array
    {
        if (! $this->legacyTableExists('tbl_ludo_card')) {
            return [];
        }

        return DB::table('tbl_ludo_card')
            ->select('step_no', 'card', 'packed')
            ->where('game_id', $gameId)
            ->get()
            ->all();
    }

    protected function legacyChaal(int $gameId, object $lastLog, object $game): int
    {
        if ((int) $lastLog->step === 6) {
            return (int) $lastLog->user_id;
        }

        $gameUsers = $this->legacyGameAllUsers($gameId);
        if (count($gameUsers) === 0) {
            return 0;
        }
        $winnerArr = [
            (int) ($game->winner_id ?? 0),
            (int) ($game->winner_id_2 ?? 0),
            (int) ($game->winner_id_3 ?? 0),
        ];

        $element = 0;
        foreach ($gameUsers as $key => $value) {
            if ((int) $value->user_id === (int) $lastLog->user_id) {
                $element = $key;
                break;
            }
        }

        $chaal = 0;
        foreach ($gameUsers as $key => $value) {
            $index = ($key + $element) % count($gameUsers);
            if ($key > 0) {
                $candidate = $gameUsers[$index];
                if (! $candidate->packed && ! in_array((int) $candidate->user_id, $winnerArr, true)) {
                    $chaal = (int) $candidate->user_id;
                    break;
                }
            }
        }

        return $chaal;
    }

    protected function legacyGameAllUsers(int $gameId): array
    {
        if (! $this->legacyTableExists('tbl_ludo_card') || ! $this->legacyTableExists('tbl_users')) {
            return [];
        }

        return DB::table('tbl_ludo_card')
            ->select('tbl_ludo_card.*', 'tbl_users.name', 'tbl_users.user_type')
            ->join('tbl_users', 'tbl_users.id', '=', 'tbl_ludo_card.user_id')
            ->where('tbl_ludo_card.game_id', $gameId)
            ->where('tbl_ludo_card.step_no', '<', 57)
            ->groupBy('tbl_ludo_card.user_id')
            ->get()
            ->all();
    }

    protected function legacyTableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable $exception) {
            return false;
        }
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
