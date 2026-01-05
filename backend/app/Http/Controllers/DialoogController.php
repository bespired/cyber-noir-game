<?php

namespace App\Http\Controllers;

use App\Models\Dialoog;
use Illuminate\Http\Request;
use App\Models\GameState;
use Illuminate\Support\Facades\Auth;

class DialoogController extends Controller
{
    public function index()
    {
        return Dialoog::with('personage')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'personage_id' => 'required|exists:personages,id',
            'titel' => 'required|string|max:255',
            'tree' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        return Dialoog::create($validated);
    }

    public function show(Dialoog $dialoog)
    {
        $dialoog->load(['personage.artwork']);
        return response()->json($dialoog);
    }

    public function update(Request $request, Dialoog $dialoog)
    {
        $validated = $request->validate([
            'personage_id' => 'exists:personages,id',
            'titel' => 'string|max:255',
            'tree' => 'array',
            'is_active' => 'boolean',
        ]);

        $dialoog->update($validated);
        return $dialoog;
    }

    public function destroy(Dialoog $dialoog)
    {
        $dialoog->delete();
        return response()->noContent();
    }

    /**
     * Check which options are available for the user based on their game state.
     */
    public function checkOptions(Request $request, Dialoog $dialoog)
    {
        $user = Auth::user();
        $gameState = GameState::firstOrCreate(['user_id' => $user->id]);

        $nodeId = $request->input('nodeId');
        $tree = $dialoog->tree;

        if (!isset($tree['nodes'][$nodeId])) {
            return response()->json(['error' => 'Node not found'], 404);
        }

        $node = $tree['nodes'][$nodeId];
        $availableOptions = [];

        foreach ($node['options'] as $option) {
            $conditions = $option['conditions'] ?? [];
            $isAvailable = true;

            foreach ($conditions as $condition) {
                if (!$gameState->checkCondition($condition)) {
                    $isAvailable = false;
                    break;
                }
            }

            if ($isAvailable) {
                $availableOptions[] = $option['id'];
            }
        }

        return response()->json(['available_options' => $availableOptions]);
    }

    /**
     * Perform actions associated with a selected option.
     */
    public function performAction(Request $request, Dialoog $dialoog)
    {
        $user = Auth::user();
        $gameState = GameState::firstOrCreate(['user_id' => $user->id]);

        $actions = $request->input('actions', []);

        foreach ($actions as $action) {
            $gameState->performAction($action);
        }

        return response()->json(['status' => 'success', 'gameState' => $gameState]);
    }
}
