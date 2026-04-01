<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use App\Models\Game;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GameController extends Controller
{
    public function index(): View
    {
        return view('admin.games.index', [
            'games' => Game::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(20),
        ]);
    }

    public function update(Request $request, Game $game): RedirectResponse
    {
        $validated = $request->validate([
            'is_visible' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'tournaments_enabled' => ['required', 'boolean'],
        ]);

        $game->update([
            'is_visible' => (bool) $validated['is_visible'],
            'is_active' => (bool) $validated['is_active'],
            'tournaments_enabled' => (bool) $validated['tournaments_enabled'],
        ]);

        return back()->with('status', "{$game->name} settings updated.");
    }
}
