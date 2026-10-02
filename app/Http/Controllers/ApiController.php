<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\CitaService;

class ApiController extends Controller
{
    public function slots(Request $request, CitaService $citaService)
    {
        $request->validate([
            'abogado_id' => ['required', 'integer', Rule::exists('usuarios', 'id')->where('rol', 'abogado')->where('activo', true)],
            'fecha'      => 'required|date_format:Y-m-d',
        ]);

        $slots = $citaService->getSlotsDisponibles(
            (int) $request->abogado_id,
            $request->fecha
        );

        return response()->json($slots);
    }
}
