<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AbogadoController extends Controller
{
    public function dashboard()
    {
        $abogado = Auth::user();

        $citasHoy = $abogado->citasComoAbogado()
            ->where('fecha', today())
            ->where('estado', 'confirmada')
            ->with('cliente')
            ->orderBy('hora_inicio')
            ->get();

        $proximasCitas = $abogado->citasComoAbogado()
            ->where('fecha', '>', today())
            ->where('estado', 'confirmada')
            ->with('cliente')
            ->orderBy('fecha')->orderBy('hora_inicio')
            ->take(5)
            ->get();

        return view('abogado.dashboard', compact('citasHoy', 'proximasCitas'));
    }

    public function agenda()
    {
        $abogado = Auth::user();

        // Citas del mes actual para el calendario
        $citas = $abogado->citasComoAbogado()
            ->where('estado', 'confirmada')
            ->whereMonth('fecha', now()->month)
            ->whereYear('fecha', now()->year)
            ->with('cliente')
            ->get()
            ->map(fn($c) => [
                'title' => $c->hora_inicio . ' — ' . $c->cliente->nombre,
                'start' => $c->fecha->format('Y-m-d') . 'T' . $c->hora_inicio,
                'end'   => $c->fecha->format('Y-m-d') . 'T' . $c->hora_fin,
                'color' => '#C9A84C',
            ]);

        return view('abogado.agenda', compact('citas'));
    }
}
