<?php

namespace App\Http\Controllers;

use App\Models\Locatie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocatieController extends Controller
{
    public function index()
    {
        return Locatie::with(['aanwijzingen', 'artwork', 'scenes'])
            ->orderBy('volgorde')
            ->orderBy('id')
            ->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'naam' => 'required|string|max:255',
            'beschrijving' => 'required|string',
            'notities' => 'nullable|string',
        ]);

        // Get max order and add new one at the end
        $validated['volgorde'] = Locatie::max('volgorde') + 1;

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
            'volgorde' => 'integer',
        ]);

        $locatie->update($validated);

        return $locatie;
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:locaties,id',
        ]);

        $ids = $request->input('ids');

        DB::transaction(function () use ($ids) {
            foreach ($ids as $index => $id) {
                Locatie::where('id', $id)->update(['volgorde' => $index + 1]);
            }
        });

        return response()->json(['message' => 'Order updated successfully']);
    }

    public function destroy(Locatie $locatie)
    {
        $locatie->delete();
        return response()->noContent();
    }
}
