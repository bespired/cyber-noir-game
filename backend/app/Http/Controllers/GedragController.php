<?php

namespace App\Http\Controllers;

use App\Models\Gedrag;
use Illuminate\Http\Request;

class GedragController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Gedrag::orderBy('naam', 'asc')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'naam' => 'required|string|max:255',
            'beschrijving' => 'nullable|string',
            'acties' => 'nullable|array',
        ]);

        return Gedrag::create($validated);
    }

    public function show(Gedrag $gedrag)
    {
        return $gedrag;
    }

    public function update(Request $request, Gedrag $gedrag)
    {
        $validated = $request->validate([
            'naam' => 'string|max:255',
            'beschrijving' => 'nullable|string',
            'acties' => 'nullable|array',
        ]);

        $gedrag->update($validated);
        return $gedrag;
    }

    public function destroy(Gedrag $gedrag)
    {
        $gedrag->delete();
        return response()->noContent();
    }
}
