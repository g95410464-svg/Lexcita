<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\HorarioDisponible;

class AbogadoController extends Controller
{
    public function dashboard()
    {
        $abogado = Auth::user();

        $citasHoy = $abogado->citasComoAbogado()
            ->where('fecha', today())
            ->where('estado', 'confirmada')
            ->with(['cliente', 'videoRoom'])
            ->orderBy('hora_inicio')
            ->get();

        $proximasCitas = $abogado->citasComoAbogado()
            ->where('fecha', '>', today())
            ->where('estado', 'confirmada')
            ->with(['cliente', 'videoRoom'])
            ->orderBy('fecha')->orderBy('hora_inicio')
            ->take(5)
            ->get();

        return view('abogado.dashboard', compact('citasHoy', 'proximasCitas'));
    }

    public function agenda()
    {
        $abogado = Auth::user();

        $citasQuery = $abogado->citasComoAbogado()
            ->where('estado', 'confirmada')
            ->with(['cliente', 'videoRoom']); // eager load para evitar N+1

        if (config('organization.id') === 'servicios-legales-integrados') {
            $citasQuery->whereBetween('fecha', [now()->subYear()->startOfYear()->toDateString(), now()->addYear()->endOfYear()->toDateString()]);
        } else {
            $citasQuery->whereMonth('fecha', now()->month)->whereYear('fecha', now()->year);
        }

        $citas = $citasQuery->get()
            ->map(fn($c) => [
                'title' => $c->hora_inicio . ' — ' . $c->cliente->nombre,
                'start' => $c->fecha->format('Y-m-d') . 'T' . $c->hora_inicio,
                'end'   => $c->fecha->format('Y-m-d') . 'T' . $c->hora_fin,
                'color' => '#C9A84C',
                // Datos para el botón "Unirse a videollamada" en el calendario
                'extendedProps' => [
                    'modalidad'  => $c->modalidad,
                    'room_token' => $c->videoRoom?->room_token,
                    'url_sala'   => $c->videoRoom ? route('video.sala', $c->videoRoom->room_token) : null,
                ],
            ]);

        if (config('organization.id') === 'servicios-legales-integrados') {
            // Repite en el calendario los bloques semanales de disponibilidad SLI.
            $diaMap = HorarioDisponible::diaSemanaMap();
            $horarios = $abogado->horarios()
                ->where('activo', true)
                ->get()
                ->map(function ($horario) use ($diaMap) {
                    $dia = $diaMap[$horario->dia_semana] ?? null;
                    if (!$dia) {
                        return null;
                    }

                    return [
                        'title' => 'Horario disponible',
                        'daysOfWeek' => [$dia],
                        'startTime' => substr((string) $horario->hora_inicio, 0, 5),
                        'endTime' => substr((string) $horario->hora_fin, 0, 5),
                        'startRecur' => now()->startOfWeek()->toDateString(),
                        'display' => 'background',
                        'color' => '#dce8f0',
                        'overlap' => false,
                    ];
                })
                ->filter()
                ->values();

            $citas = $citas->concat($horarios)->values();
        }

        return view('abogado.agenda', compact('citas'));
    }
}
