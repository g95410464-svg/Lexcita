<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\{Cita, Usuario};
use Carbon\Carbon;

class ClienteController extends Controller
{
    public function dashboard()
    {
        $cliente = Auth::user();
        $proximasCitas = $cliente->citasComoCliente()
            ->where('estado', 'confirmada')
            ->where('fecha', '>=', today())
            ->with('abogado')
            ->orderBy('fecha')->orderBy('hora_inicio')
            ->take(3)
            ->get();

        $totalCitas = $cliente->citasComoCliente()->count();

        return view('cliente.dashboard', compact('proximasCitas', 'totalCitas'));
    }

    public function nuevaCita()
    {
        $abogados = Usuario::abogadosActivos()->get();
        return view('cliente.nueva-cita', compact('abogados'));
    }

    public function crearCita(Request $request)
    {
        $data = $request->validate([
            'abogado_id'  => 'required|exists:usuarios,id',
            'fecha'       => 'required|date|after:today',
            'hora_inicio' => 'required|date_format:H:i',
            'tipo'        => 'required|in:consulta_general,derecho_familiar,derecho_penal,derecho_laboral,derecho_civil,otro',
            'modalidad'   => 'required|in:presencial,virtual',
            'descripcion' => 'nullable|string|max:500',
        ]);

        // Calcular hora_fin (slots de 60 min)
        $horaFin = Carbon::createFromFormat('H:i', $data['hora_inicio'])->addHour()->format('H:i');

        // Validar que el slot siga libre (doble check server-side)
        $conflicto = Cita::where('abogado_id', $data['abogado_id'])
            ->where('fecha', $data['fecha'])
            ->where('estado', '!=', 'cancelada')
            ->where('hora_inicio', $data['hora_inicio'])
            ->exists();

        if ($conflicto) {
            return back()->withErrors(['hora_inicio' => 'Ese horario ya fue reservado. Por favor elige otro.'])->withInput();
        }

        $cita = Cita::create([
            'codigo'      => Cita::generarCodigo(),
            'cliente_id'  => Auth::id(),
            'abogado_id'  => $data['abogado_id'],
            'fecha'       => $data['fecha'],
            'hora_inicio' => $data['hora_inicio'],
            'hora_fin'    => $horaFin,
            'tipo'        => $data['tipo'],
            'modalidad'   => $data['modalidad'],
            'descripcion' => $data['descripcion'] ?? null,
            'estado'      => 'pendiente_pago',
            'monto'       => 35.00,
        ]);

        // Redirigir a crear sesión de pago
        return redirect()->route('pago.crear-sesion', $cita->id);
    }

    public function misCitas()
    {
        $citas = Auth::user()->citasComoCliente()
            ->with('abogado')
            ->orderByDesc('fecha')
            ->paginate(10);

        return view('cliente.mis-citas', compact('citas'));
    }

    public function cancelarCita(int $id)
    {
        $cita = Cita::where('id', $id)
            ->where('cliente_id', Auth::id())
            ->firstOrFail();

        if (!$cita->puedeCancelarse()) {
            return back()->withErrors(['cancelar' => 'Solo puedes cancelar con más de 24 horas de anticipación.']);
        }

        $cita->update(['estado' => 'cancelada']);

        return back()->with('success', 'Cita cancelada correctamente.');
    }
}