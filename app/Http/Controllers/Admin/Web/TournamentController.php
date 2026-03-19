<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V1\TournamentStoreRequest;
use App\Http\Requests\Admin\V1\TournamentUpdateRequest;
use App\Models\Game;
use App\Models\Tournament;
use App\Services\Tournament\TournamentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TournamentController extends Controller
{
    public function __construct(
        protected TournamentService $tournamentService
    ) {
    }

    public function index(): View
    {
        return view('admin.tournaments.index', [
            'tournaments' => Tournament::query()
                ->with(['game', 'prizes'])
                ->latest()
                ->paginate(20),
            'games' => Game::query()->where('is_active', true)->orderBy('name')->get(),
            'editingTournament' => null,
        ]);
    }

    public function create(): View
    {
        return view('admin.tournaments.index', [
            'tournaments' => Tournament::query()
                ->with(['game', 'prizes'])
                ->latest()
                ->paginate(20),
            'games' => Game::query()->where('is_active', true)->orderBy('name')->get(),
            'editingTournament' => null,
        ]);
    }

    public function edit(Tournament $tournament): View
    {
        return view('admin.tournaments.index', [
            'tournaments' => Tournament::query()
                ->with(['game', 'prizes'])
                ->latest()
                ->paginate(20),
            'games' => Game::query()->where('is_active', true)->orderBy('name')->get(),
            'editingTournament' => $tournament->load('prizes'),
        ]);
    }

    public function store(TournamentStoreRequest $request): RedirectResponse
    {
        $this->tournamentService->create($request->validated());

        return redirect()
            ->route('admin.tournaments.index')
            ->with('status', 'Tournament created successfully.');
    }

    public function update(TournamentUpdateRequest $request, Tournament $tournament): RedirectResponse
    {
        $this->tournamentService->update($tournament, $request->validated());

        return redirect()
            ->route('admin.tournaments.index')
            ->with('status', 'Tournament updated successfully.');
    }
}
