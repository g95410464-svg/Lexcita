<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Cita;
use App\Services\WhatsAppService;

class PagoController extends Controller
{
    public function mostrarInstrucciones(int $citaId)
    {
        $cita = Cita::where('id', $citaId)
            ->where('cliente_id', Auth::id())
            ->where('estado', 'pendiente_pago')
            ->with(['abogado'])
            ->firstOrFail();

        return view('pago.instrucciones', compact('cita'));
    }

    public function exito(Request $request)
    {
        $cita = Cita::where('cliente_id', Auth::id())
            ->where('estado', 'pendiente_pago')
            ->orderByDesc('created_at')
            ->with(['cliente', 'abogado'])
            ->first();

        if ($cita) {
            $cita->update([
                'estado'            => 'confirmada',
                'stripe_session_id' => 'PAYPAL-' . now()->timestamp,
            ]);

            try {
                app(WhatsAppService::class)->enviarConfirmacion($cita);
            } catch (\Exception $e) {}
        }

        return view('pago.exito', compact('cita'));
    }

    public function cancelado()
    {
        return view('pago.cancelado');
    }

    public function crearSesion(int $citaId)
    {
        return redirect()->route('pago.instrucciones', $citaId);
    }

    public function confirmarManual(int $citaId)
    {
        $cita = Cita::where('id', $citaId)
            ->where('estado', 'pendiente_pago')
            ->with(['cliente', 'abogado'])
            ->firstOrFail();

        $cita->update(['estado' => 'confirmada']);

        try {
            app(WhatsAppService::class)->enviarConfirmacion($cita);
        } catch (\Exception $e) {}

        return back()->with('success', "Cita {$cita->codigo} confirmada correctamente.");
    }

    public function cancelarManual(int $citaId)
    {
        $cita = Cita::where('id', $citaId)
            ->whereIn('estado', ['pendiente_pago', 'confirmada'])
            ->firstOrFail();

        $cita->update(['estado' => 'cancelada']);

        return back()->with('success', "Cita {$cita->codigo} cancelada.");
    }
}