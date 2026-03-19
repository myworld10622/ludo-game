<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\TournamentEntryResource;
use App\Models\Tournament;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TournamentEntryController extends Controller
{
    public function index(Request $request, Tournament $tournament): JsonResponse
    {
        $query = $tournament->entries()->with('user')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        return response()->json([
            'success' => true,
            'data' => TournamentEntryResource::collection($query->paginate(50)),
        ]);
    }
}
