<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use App\Models\HomepageTournamentCard;
use App\Models\Tournament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomepageCardController extends Controller
{
    public function index(): View
    {
        $cards       = HomepageTournamentCard::orderBy('sort_order')->get();
        $tournaments = Tournament::whereIn('status', ['registration_open', 'in_progress'])
                                 ->orderByDesc('created_at')
                                 ->get(['id', 'name']);

        return view('admin.homepage-cards.index', compact('cards', 'tournaments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'          => 'required|string|max:120',
            'icon'          => 'nullable|string|max:10',
            'description'   => 'nullable|string|max:400',
            'card_color'    => 'required|in:gold,blue,purple',
            'status_badge'  => 'required|in:live,open,soon',
            'status_text'   => 'required|string|max:60',
            'meta1_label'   => 'required|string|max:40',
            'meta1_value'   => 'required|string|max:40',
            'meta2_label'   => 'required|string|max:40',
            'meta2_value'   => 'required|string|max:40',
            'meta3_label'   => 'required|string|max:40',
            'meta3_value'   => 'required|string|max:40',
            'tournament_id' => 'nullable|exists:tournaments,id',
            'is_visible'    => 'boolean',
        ]);

        $data['sort_order'] = HomepageTournamentCard::max('sort_order') + 1;
        $data['is_visible'] = $request->boolean('is_visible', true);

        HomepageTournamentCard::create($data);

        return redirect()->route('admin.homepage-cards.index')
                         ->with('status', 'Homepage card created successfully!');
    }

    public function update(Request $request, HomepageTournamentCard $homepageCard): RedirectResponse
    {
        $data = $request->validate([
            'name'          => 'required|string|max:120',
            'icon'          => 'nullable|string|max:10',
            'description'   => 'nullable|string|max:400',
            'card_color'    => 'required|in:gold,blue,purple',
            'status_badge'  => 'required|in:live,open,soon',
            'status_text'   => 'required|string|max:60',
            'meta1_label'   => 'required|string|max:40',
            'meta1_value'   => 'required|string|max:40',
            'meta2_label'   => 'required|string|max:40',
            'meta2_value'   => 'required|string|max:40',
            'meta3_label'   => 'required|string|max:40',
            'meta3_value'   => 'required|string|max:40',
            'tournament_id' => 'nullable|exists:tournaments,id',
            'is_visible'    => 'boolean',
        ]);

        $data['is_visible'] = $request->boolean('is_visible', true);
        $homepageCard->update($data);

        return redirect()->route('admin.homepage-cards.index')
                         ->with('status', 'Card "' . $homepageCard->name . '" updated!');
    }

    public function destroy(HomepageTournamentCard $homepageCard): RedirectResponse
    {
        $homepageCard->delete();

        return redirect()->route('admin.homepage-cards.index')
                         ->with('status', 'Card deleted.');
    }

    public function reorder(Request $request)
    {
        $request->validate(['order' => 'required|array', 'order.*' => 'integer']);

        foreach ($request->order as $position => $id) {
            HomepageTournamentCard::where('id', $id)
                                  ->update(['sort_order' => $position + 1]);
        }

        return response()->json(['success' => true]);
    }
}
