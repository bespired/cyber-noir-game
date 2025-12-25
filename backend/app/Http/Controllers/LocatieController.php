<?php

namespace App\Http\Controllers;

use App\Models\Locatie;
use App\Models\Sector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LocatieController extends Controller
{
    public function index()
    {
        $locaties = Locatie::with(['aanwijzingen', 'artwork', 'scenes.sector'])
            ->orderBy('volgorde')
            ->orderBy('id')
            ->get();

        return $locaties->map(function ($locatie) {
            $hasGlb = false;
            $glbSectorId = null;
            foreach ($locatie->scenes as $scene) {
                if (!$scene->sector)
                    continue;
                $sectorSlug = Str::slug($scene->sector->naam);
                $locationSlug = Str::slug($locatie->naam);
                if (Storage::disk('public')->exists("glb/{$sectorSlug}--{$locationSlug}.glb")) {
                    $hasGlb = true;
                    $glbSectorId = $scene->sector->id;
                    break;
                }
            }
            $locatie->has_glb = $hasGlb;
            $locatie->glb_sector_id = $glbSectorId;
            return $locatie;
        });
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
        $locatie->load(['aanwijzingen', 'artwork', 'scenes.sector']);

        $locatieData = $locatie->toArray();

        // Check for GLB files for each sector this location is in
        $sectors = $locatie->scenes->pluck('sector')->unique('id');
        $glbStatus = [];

        foreach ($sectors as $sector) {
            if (!$sector)
                continue;

            $sectorSlug = Str::slug($sector->naam);
            $locationSlug = Str::slug($locatie->naam);
            $filename = "{$sectorSlug}--{$locationSlug}.glb";
            $exists = Storage::disk('public')->exists("glb/{$filename}");

            $glbStatus[] = [
                'sector_id' => $sector->id,
                'sector_naam' => $sector->naam,
                'exists' => $exists,
                'url' => $exists ? Storage::url("glb/{$filename}") : null,
                'filename' => $filename
            ];
        }

        $locatieData['glb_status'] = $glbStatus;

        return response()->json($locatieData);
    }

    public function update(Request $request, Locatie $locatie)
    {
        $validated = $request->validate([
            'naam' => 'string|max:255',
            'beschrijving' => 'string',
            'notities' => 'nullable|string',
            'volgorde' => 'integer',
            'spawn_points' => 'nullable|array',
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

    public function uploadGlb(Request $request, Locatie $locatie)
    {
        $request->validate([
            'sector_id' => 'required|exists:sectoren,id',
            'glb' => 'required|file|max:51200', // Max 50MB
        ]);

        $sector = Sector::findOrFail($request->sector_id);
        $file = $request->file('glb');

        $sectorSlug = Str::slug($sector->naam);
        $locationSlug = Str::slug($locatie->naam);
        $filename = "{$sectorSlug}--{$locationSlug}.glb";

        // Store in public disk
        Storage::disk('public')->putFileAs('glb', $file, $filename);

        return response()->json([
            'message' => 'GLB succesvol geüpload!',
            'path' => Storage::url('glb/' . $filename),
            'filename' => $filename
        ]);
    }
}
