<?php

namespace App\Http\Controllers;

use App\Models\Sector;
use Illuminate\Http\Request;

class SectorController extends Controller
{
    public function index()
    {
        return Sector::with('scenes')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'naam' => 'required|string|max:255|unique:sectoren',
            'beschrijving' => 'nullable|string',
            'kaart_coordinaten' => 'nullable|string',
            'is_ontdekt' => 'boolean',
            'x' => 'integer',
            'y' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ]);

        return Sector::create($validated);
    }

    public function show(Sector $sector)
    {
        $sector->load(['scenes.artwork', 'scenes.locatie.artwork', 'artwork']);
        return response()->json($sector);
    }

    public function update(Request $request, Sector $sector)
    {
        $validated = $request->validate([
            'naam' => 'string|max:255|unique:sectoren,naam,' . $sector->id,
            'beschrijving' => 'nullable|string',
            'kaart_coordinaten' => 'nullable|string',
            'is_ontdekt' => 'boolean',
            'x' => 'integer',
            'y' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ]);

        $sector->update($validated);

        return $sector->load('artwork');
    }

    public function destroy(Sector $sector)
    {
        $sector->delete();
        return response()->noContent();
    }
}
