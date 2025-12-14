<?php

namespace App\Http\Controllers;

use App\Models\Personage;
use Illuminate\Http\Request;

class PersonageController extends Controller
{
    public function index()
    {
        return Personage::with(['aanwijzingen', 'artwork'])->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'naam' => 'required|string|max:255',
            'rol' => 'required|string|max:255',
            'beschrijving' => 'required|string',
            'menselijke_status' => 'nullable|string',
            'replicant_status' => 'nullable|string',
            'motief' => 'nullable|string',
            'is_replicant' => 'boolean',
        ]);

        return Personage::create($validated);
    }

    public function show(Personage $personage)
    {
        $personage->load(['aanwijzingen', 'artwork']);
        return response()->json($personage);
    }

    public function update(Request $request, Personage $personage)
    {
        $validated = $request->validate([
            'naam' => 'string|max:255',
            'rol' => 'string|max:255',
            'beschrijving' => 'string',
            'menselijke_status' => 'nullable|string',
            'replicant_status' => 'nullable|string',
            'motief' => 'nullable|string',
            'is_replicant' => 'boolean',
        ]);

        $personage->update($validated);

        return $personage;
    }

    public function destroy(Personage $personage)
    {
        $personage->delete();
        return response()->noContent();
    }
}
