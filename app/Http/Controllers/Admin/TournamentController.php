<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Tournament\TournamentActionRequest;
use App\Http\Requests\Admin\Tournament\StoreTournamentRequest;
use App\Http\Requests\Admin\Tournament\UpdateTournamentRequest;
use App\Http\Resources\Admin\TournamentResource;
use App\Models\Tournament;
use App\Services\Tournament\TournamentAdminService;
use App\Services\Tournament\TournamentAdminLifecycleService;
use App\Services\Tournament\TournamentRoundSeedingService;
use App\Services\Tournament\TournamentRoomProvisioningService;
use App\Services\Tournament\TournamentRoundLifecycleService;
use App\Services\Tournament\TournamentLudoRoomProvisionService;
use App\Services\Tournament\TournamentSettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TournamentController extends Controller
{
    public function __construct(
        private readonly TournamentAdminService $tournamentAdminService,
        private readonly TournamentAdminLifecycleService $tournamentAdminLifecycleService,
        private readonly TournamentRoundSeedingService $tournamentRoundSeedingService,
        private readonly TournamentRoomProvisioningService $tournamentRoomProvisioningService,
        private readonly TournamentRoundLifecycleService $tournamentRoundLifecycleService,
        private readonly TournamentLudoRoomProvisionService $tournamentLudoRoomProvisionService,
        private readonly TournamentSettlementService $tournamentSettlementService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $query = Tournament::query()->with(['game', 'prizes'])->latest();

        if ($request->filled('game_id')) {
            $query->where('game_id', $request->integer('game_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json([
            'success' => true,
            'data' => TournamentResource::collection($query->paginate(20)),
        ]);
    }

    public function store(StoreTournamentRequest $request): JsonResponse
    {
        $tournament = $this->tournamentAdminService->create(
            $request->validated(),
            auth('admin')->id()
        );

        return response()->json([
            'success' => true,
            'message' => 'Tournament created successfully.',
            'data' => new TournamentResource($tournament),
        ], 201);
    }

    public function show(Tournament $tournament): JsonResponse
    {
        $tournament->load(['game', 'prizes']);

        return response()->json([
            'success' => true,
            'data' => new TournamentResource($tournament),
        ]);
    }

    public function update(UpdateTournamentRequest $request, Tournament $tournament): JsonResponse
    {
        $tournament = $this->tournamentAdminService->update(
            $tournament,
            $request->validated(),
            auth('admin')->id()
        );

        return response()->json([
            'success' => true,
            'message' => 'Tournament updated successfully.',
            'data' => new TournamentResource($tournament),
        ]);
    }

    public function publish(TournamentActionRequest $request, Tournament $tournament): JsonResponse
    {
        $tournament = $this->tournamentAdminLifecycleService->publish($tournament, auth('admin')->id());

        return response()->json([
            'success' => true,
            'message' => 'Tournament published successfully.',
            'data' => new TournamentResource($tournament),
        ]);
    }

    public function lock(TournamentActionRequest $request, Tournament $tournament): JsonResponse
    {
        $tournament = $this->tournamentAdminLifecycleService->lockEntries($tournament, auth('admin')->id());

        return response()->json([
            'success' => true,
            'message' => 'Tournament entries locked successfully.',
            'data' => new TournamentResource($tournament),
        ]);
    }

    public function cancel(TournamentActionRequest $request, Tournament $tournament): JsonResponse
    {
        $tournament = $this->tournamentAdminLifecycleService->cancel(
            $tournament,
            $request->validated()['reason'] ?? null,
            auth('admin')->id()
        );

        return response()->json([
            'success' => true,
            'message' => 'Tournament cancelled successfully.',
            'data' => new TournamentResource($tournament),
        ]);
    }

    public function seedLudo(TournamentActionRequest $request, Tournament $tournament): JsonResponse
    {
        $links = $this->tournamentRoundSeedingService->seedRound($tournament, 1);

        return response()->json([
            'success' => true,
            'message' => 'Tournament entries seeded into Ludo tables.',
            'data' => [
                'match_links_created' => $links->count(),
            ],
        ]);
    }

    public function provisionLudoRooms(TournamentActionRequest $request, Tournament $tournament): JsonResponse
    {
        $queued = filter_var($request->input('queued', false), FILTER_VALIDATE_BOOL);

        if ($queued) {
            $this->tournamentRoomProvisioningService->dispatch($tournament, 1);

            return response()->json([
                'success' => true,
                'message' => 'Ludo tournament room provisioning queued successfully.',
                'data' => [
                    'queued' => true,
                    'round_no' => 1,
                ],
            ]);
        }

        $rooms = $this->tournamentRoomProvisioningService->provisionRound($tournament, 1);

        return response()->json([
            'success' => true,
            'message' => 'Ludo tournament rooms provisioned successfully.',
            'data' => [
                'rooms_created' => $rooms->count(),
            ],
        ]);
    }

    public function settle(Request $request, Tournament $tournament): JsonResponse
    {
        $validated = $request->validate([
            'rankings' => ['required', 'array', 'min:1'],
            'rankings.*.tournament_entry_id' => ['required', 'integer', 'exists:tournament_entries,id'],
            'rankings.*.final_rank' => ['required', 'integer', 'min:1'],
            'rankings.*.score' => ['nullable', 'numeric'],
        ]);

        $tournament = $this->tournamentSettlementService->settle($tournament, $validated['rankings']);

        return response()->json([
            'success' => true,
            'message' => 'Tournament settled successfully.',
            'data' => new TournamentResource($tournament),
        ]);
    }

    public function retryRoundLifecycle(TournamentActionRequest $request, Tournament $tournament): JsonResponse
    {
        $roundNo = (int) ($request->input('round_no') ?? 1);
        $this->tournamentRoundLifecycleService->dispatch($tournament, $roundNo);

        return response()->json([
            'success' => true,
            'message' => 'Tournament round lifecycle queued successfully.',
            'data' => [
                'queued' => true,
                'round_no' => $roundNo,
            ],
        ]);
    }

    public function retryRoundSeeding(TournamentActionRequest $request, Tournament $tournament): JsonResponse
    {
        $roundNo = (int) ($request->input('round_no') ?? 1);
        $this->tournamentRoundSeedingService->dispatch($tournament, $roundNo);

        return response()->json([
            'success' => true,
            'message' => 'Tournament round seeding queued successfully.',
            'data' => [
                'queued' => true,
                'round_no' => $roundNo,
            ],
        ]);
    }

    public function retryRoundProvisioning(TournamentActionRequest $request, Tournament $tournament): JsonResponse
    {
        $roundNo = (int) ($request->input('round_no') ?? 1);
        $this->tournamentRoomProvisioningService->dispatch($tournament, $roundNo);

        return response()->json([
            'success' => true,
            'message' => 'Tournament round provisioning queued successfully.',
            'data' => [
                'queued' => true,
                'round_no' => $roundNo,
            ],
        ]);
    }
}
