<?php

namespace App\Http\Controllers;

use App\Models\ScenePersonage;
use Illuminate\Http\Request;

class ScenePersonageController extends Controller
{
    public function index(Request $request)
    {
        $query = ScenePersonage::with(['personage', 'gedrag', 'dialoog']);

        if ($request->has('scene_id')) {
            $query->where('scene_id', $request->scene_id);
        }

        return $query->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'scene_id' => 'required|exists:scenes,id',
            'personage_id' => 'required|exists:personages,id',
            'spawn_point_name' => 'nullable',
            'spawn_condition' => 'nullable|array',
            'gedrag_id' => 'nullable|exists:gedragingen,id',
            'dialoog_id' => 'nullable|exists:dialogen,id',
        ]);

        return ScenePersonage::create($validated);
    }

    public function show(ScenePersonage $scenePersonage)
    {
        return $scenePersonage->load(['personage', 'gedrag', 'dialoog']);
    }

    public function update(Request $request, ScenePersonage $scenePersonage)
    {
        $validated = $request->validate([
            'spawn_point_name' => 'nullable',
            'spawn_condition' => 'nullable|array',
            'gedrag_id' => 'nullable|exists:gedragingen,id',
            'dialoog_id' => 'nullable|exists:dialogen,id',
        ]);

        $scenePersonage->update($validated);
        return $scenePersonage;
    }

    public function destroy(ScenePersonage $scenePersonage)
    {
        $scenePersonage->delete();
        return response()->noContent();
    }
}
