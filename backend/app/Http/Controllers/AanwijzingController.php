<?php

namespace App\Http\Controllers;

use App\Models\Aanwijzing;
use Illuminate\Http\Request;

class AanwijzingController extends Controller
{
    public function index()
    {
        return Aanwijzing::with(['personage', 'locatie', 'artwork'])->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titel' => 'required|string|max:255',
            'beschrijving' => 'required|string',
            'type' => 'nullable|string|in:image,object,gamestate',
            'data' => 'nullable|array',
            'personage_id' => 'nullable|exists:personages,id',
            'locatie_id' => 'nullable|exists:locaties,id',
            'is_kritisch' => 'boolean',
        ]);

        return Aanwijzing::create($validated);
    }

    public function show(Aanwijzing $aanwijzing)
    {
        $aanwijzing->load(['personage', 'locatie', 'artwork']);
        return response()->json($aanwijzing);
    }

    public function update(Request $request, Aanwijzing $aanwijzing)
    {
        $validated = $request->validate([
            'titel' => 'string|max:255',
            'beschrijving' => 'string',
            'type' => 'nullable|string|in:image,object,gamestate',
            'data' => 'nullable|array',
            'personage_id' => 'nullable|exists:personages,id',
            'locatie_id' => 'nullable|exists:locaties,id',
            'is_kritisch' => 'boolean',
        ]);

        $aanwijzing->update($validated);

        return $aanwijzing;
    }

    public function destroy(Aanwijzing $aanwijzing)
    {
        $aanwijzing->delete();
        return response()->noContent();
    }
}
