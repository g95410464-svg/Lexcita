<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CitaService;

class ApiController extends Controller
{
    public function slots(Request $request, CitaService $citaService)
    {
        $request->validate([
            'abogado_id' => 'required|exists:usuarios,id',
            'fecha'      => 'required|date',
        ]);

        $slots = $citaService->getSlotsDisponibles(
            (int) $request->abogado_id,
            $request->fecha
        );

        return response()->json($slots);
    }
}
