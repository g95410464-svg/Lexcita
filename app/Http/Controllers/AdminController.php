<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\{Usuario, Cita, HorarioDisponible};
use App\Services\{HorarioService, CitaService};

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'total_citas'      => Cita::count(),
            'citas_hoy'        => Cita::where('fecha', today())->count(),
            'confirmadas'      => Cita::confirmadas()->count(),
            'total_clientes'   => Usuario::where('rol', 'cliente')->count(),
            'total_abogados'   => Usuario::where('rol', 'abogado')->count(),
            'ingresos'         => Cita::confirmadas()->sum('monto'),
        ];

        $citasRecientes = Cita::with(['cliente', 'abogado'])
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        return view('interno.dashboard', compact('stats', 'citasRecientes'));
    }

    public function abogados()
    {
        $abogados = Usuario::where('rol', 'abogado')
            ->withCount(['citasComoAbogado as total_citas'])
            ->get();

        return view('interno.abogados', compact('abogados'));
    }

    public function crearAbogado(Request $request)
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:120',
            'email'       => 'required|email|unique:usuarios,email',
            'password'    => 'required|min:8',
            'especialidad'=> 'required|string|max:120',
            'telefono_whatsapp' => 'nullable|string|max:20',
        ]);

        $abogado = Usuario::create([
            'nombre'             => $data['nombre'],
            'email'              => $data['email'],
            'password'           => Hash::make($data['password']),
            'rol'                => 'abogado',
            'especialidad'       => $data['especialidad'],
            'telefono_whatsapp'  => $data['telefono_whatsapp'] ?? null,
            'activo'             => true,
        ]);

        // Crear horarios Lun-Vie 08:00-17:00 automáticamente
        app(HorarioService::class)->crearHorariosDefault($abogado);

        return back()->with('success', 'Abogado registrado correctamente con horarios predeterminados.');
    }

    public function toggleAbogado(int $id)
    {
        $abogado = Usuario::where('id', $id)->where('rol', 'abogado')->firstOrFail();
        $abogado->update(['activo' => !$abogado->activo]);

        return back()->with('success', 'Estado del abogado actualizado.');
    }

    public function clientes()
    {
        $clientes = Usuario::where('rol', 'cliente')
            ->withCount('citasComoCliente as total_citas')
            ->paginate(20);

        return view('interno.clientes', compact('clientes'));
    }

    public function citas(Request $request)
    {
        $data = $request->validate([
            'estado' => 'nullable|string|in:pendiente_pago,confirmada,cancelada,completada',
            'abogado_id' => 'nullable|integer|min:1',
            'buscar' => 'nullable|string|max:120',
        ]);
        $query = Cita::with(['cliente', 'abogado']);

        if ($request->filled('estado')) {
            $query->where('estado', $data['estado']);
        }
        if ($request->filled('abogado_id')) {
            $query->where('abogado_id', $data['abogado_id']);
        }
        if ($request->filled('buscar')) {
            $buscar = $data['buscar'];
            // Group OR conditions so searching cannot bypass the other filters.
            // Eloquent binds the pattern as a value; it is never concatenated into SQL.
            $query->where(function ($q) use ($buscar) {
                $q->whereHas('cliente', fn($cliente) => $cliente->where('nombre', 'like', "%{$buscar}%"))
                    ->orWhere('codigo', 'like', "%{$buscar}%");
            });
        }

        $citas    = $query->orderByDesc('fecha')->paginate(20);
        $abogados = Usuario::where('rol', 'abogado')->get();

        return view('interno.citas', compact('citas', 'abogados'));
    }

    public function estadisticas()
    {
        // Ingresos por mes (últimos 6 meses)
        $ingresosMes = Cita::confirmadas()
            ->selectRaw("EXTRACT(YEAR FROM fecha) as ano, EXTRACT(MONTH FROM fecha) as mes, SUM(monto) as total")
            ->groupByRaw("EXTRACT(YEAR FROM fecha), EXTRACT(MONTH FROM fecha)")
            ->orderByRaw("EXTRACT(YEAR FROM fecha), EXTRACT(MONTH FROM fecha)")
            ->get();

        return view('interno.estadisticas', compact('ingresosMes'));
    }

    public function confirmarCita(int $id)
    {
        $cita = Cita::where('id', $id)
            ->where('estado', '!=', 'cancelada')
            ->with(['cliente', 'abogado'])
            ->firstOrFail();

        try {
            app(CitaService::class)->confirmar($cita);
            $cita = $cita->fresh();
            $mensaje = "Cita {$cita->codigo} confirmada correctamente.";
            if ($cita->esVirtual() && $cita->videoRoom) {
                $mensaje .= ' Sala de videollamada creada.';
            }
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['confirmar' => $e->getMessage()]);
        }

        return back()->with('success', $mensaje);
    }

    public function cancelarCita(int $id)
    {
        $cita = Cita::where('id', $id)
            ->whereIn('estado', ['confirmada'])
            ->firstOrFail();

        $cita->update(['estado' => 'cancelada']);

        return back()->with('success', "Cita {$cita->codigo} cancelada.");
    }
}
