<?php

namespace App\Http\Controllers;

use App\Models\Scene;
use Illuminate\Http\Request;

class SceneController extends Controller
{
    public function index()
    {
        return Scene::with('locatie')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'locatie_id' => 'required|exists:locaties,id',
            'titel' => 'required|string|max:255',
            'type' => 'required|string',
            'beschrijving' => 'required|string',
            'status' => 'required|string',
        ]);

        return Scene::create($validated);
    }

    public function show(Scene $scene)
    {
        $scene->load('locatie');
        return response()->json($scene);
    }

    public function update(Request $request, Scene $scene)
    {
        $validated = $request->validate([
            'locatie_id' => 'exists:locaties,id',
            'titel' => 'string|max:255',
            'type' => 'string',
            'beschrijving' => 'string',
            'status' => 'string',
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
