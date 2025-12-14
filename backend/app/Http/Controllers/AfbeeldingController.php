<?php

namespace App\Http\Controllers;

use App\Models\Afbeelding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AfbeeldingController extends Controller
{
    public function destroy(Afbeelding $afbeelding)
    {
        // Delete file from storage
        if (Storage::disk('public')->exists($afbeelding->bestandspad)) {
            Storage::disk('public')->delete($afbeelding->bestandspad);
        }

        // Delete record
        $afbeelding->delete();

        return response()->noContent();
    }
}
