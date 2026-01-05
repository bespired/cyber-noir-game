<?php

namespace App\Http\Controllers;

use App\Models\Notitie;
use Illuminate\Http\Request;

class NotitieController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Notitie::orderBy('created_at', 'desc')->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'titel' => 'required|string|max:255',
            'inhoud' => 'required|string',
            'is_afgerond' => 'boolean'
        ]);

        $notitie = Notitie::create($validated);
        return response()->json($notitie, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Notitie $notitie)
    {
        return $notitie;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Notitie $notitie)
    {
        $validated = $request->validate([
            'titel' => 'sometimes|required|string|max:255',
            'inhoud' => 'sometimes|required|string',
            'is_afgerond' => 'boolean'
        ]);

        $notitie->update($validated);
        return response()->json($notitie);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Notitie $notitie)
    {
        $notitie->delete();
        return response()->json(null, 204);
    }
}
