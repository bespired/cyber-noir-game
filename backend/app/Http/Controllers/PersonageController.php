<?php

namespace App\Http\Controllers;

use App\Models\Personage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class PersonageController extends Controller
{
    public function index()
    {
        return Personage::with(['aanwijzingen', 'artwork'])->orderBy('naam', 'asc')->get();
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
            'is_playable' => 'boolean',
        ]);

        return Personage::create($validated);
    }

    public function show(Personage $personage)
    {
        $personage->load(['aanwijzingen', 'artwork']);

        $slug = Str::slug($personage->naam);
        $filename = $slug . '.glb';
        $exists = Storage::disk('public')->exists('glb/' . $filename);

        $data = $personage->toArray();
        $data['glb_exists'] = $exists;
        $data['glb_url'] = $exists ? Storage::url('glb/' . $filename) : null;

        return response()->json($data);
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
            'is_playable' => 'boolean',
        ]);

        $personage->update($validated);

        return $this->show($personage);
    }

    public function destroy(Personage $personage)
    {
        $personage->delete();
        return response()->noContent();
    }

    public function uploadGlb(Request $request, Personage $personage)
    {
        $request->validate([
            'glb' => 'required|file|max:20480', // Max 20MB
        ]);

        $file = $request->file('glb');
        $slug = Str::slug($personage->naam);
        $filename = $slug . '.glb';

        // Store in public disk
        Storage::disk('public')->putFileAs('glb', $file, $filename);

        return response()->json([
            'message' => 'GLB succesvol geüpload!',
            'path' => Storage::url('glb/' . $filename),
            'filename' => $filename
        ]);
    }
}
