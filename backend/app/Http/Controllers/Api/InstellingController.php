<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Instelling;
use Illuminate\Http\Request;

class InstellingController extends Controller
{
    public function index()
    {
        return response()->json(Instelling::pluck('waarde', 'sleutel'));
    }

    public function show($sleutel)
    {
        $instelling = Instelling::where('sleutel', $sleutel)->first();
        if (!$instelling) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json(['waarde' => $instelling->waarde]);
    }

    public function update(Request $request, $sleutel)
    {
        $instelling = Instelling::where('sleutel', $sleutel)->first();
        if (!$instelling) {
            $instelling = new Instelling(['sleutel' => $sleutel]);
        }

        $instelling->waarde = $request->waarde;
        $instelling->save();

        return response()->json(['message' => 'INSTELLING_UPDATED', 'waarde' => $instelling->waarde]);
    }
}
