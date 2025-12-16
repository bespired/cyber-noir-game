<?php

namespace App\Http\Controllers;

use App\Models\Locatie;
use Illuminate\Http\Request;

class LocatieController extends Controller
{
    public function index()
    {
        return Locatie::with(['aanwijzingen', 'artwork'])->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'naam' => 'required|string|max:255',
            'beschrijving' => 'required|string',
            'notities' => 'nullable|string',
        ]);

        return Locatie::create($validated);
    }

    public function show(Locatie $locatie)
    {

        $locatie->load(['aanwijzingen', 'artwork']);
        return response()->json($locatie);
    }

    public function update(Request $request, Locatie $locatie)
    {
        $validated = $request->validate([
            'naam' => 'string|max:255',
            'beschrijving' => 'string',
            'notities' => 'nullable|string',
            'sector_id' => 'nullable|exists:sectoren,id',
        ]);

        $locatie->update($validated);

        return $locatie;
    }

    public function destroy(Locatie $locatie)
    {
        $locatie->delete();
        return response()->noContent();
    }
}
