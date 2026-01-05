<?php
namespace App\Http\Controllers;

use App\Models\Scene;
use Illuminate\Http\Request;

class SceneController extends Controller
{
    public function index()
    {
        $select = ['locatie', 'sector', 'scenePersonages.personage', 'scenePersonages.gedrag'];
        return Scene::with($select)->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'locatie_id'   => 'nullable',
            'sector_id'    => 'nullable',
            'titel'        => 'required|string|max:255',
            'type'         => 'required|string',
            'beschrijving' => 'required|string',
            'status'       => 'required|string',
            'data'         => 'nullable|array',
        ]);

        return Scene::create($validated);
    }

    public function show(Scene $scene)
    {
        $scene->load(['locatie.artwork', 'sector', 'artwork', 'scenePersonages.personage.artwork', 'scenePersonages.gedrag']);
        return response()->json($scene);
    }

    public function update(Request $request, Scene $scene)
    {
        $validated = $request->validate([
            'locatie_id'   => 'nullable',
            'sector_id'    => 'nullable',
            'titel'        => 'string|max:255',
            'type'         => 'string',
            'beschrijving' => 'string',
            'status'       => 'string',
            'gateways'     => 'nullable|array',
            'data'         => 'nullable|array',
        ]);
        $scene->update($validated);

        return $scene;
    }

    public function destroy(Scene $scene)
    {
        $scene->delete();
        return response()->noContent();
    }
}
