<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use App\Services\CitaService;
use App\Exceptions\BookingSchemaNotReady;
use Illuminate\Support\Facades\Log;

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

    public function retenerHorario(Request $request, CitaService $citaService)
    {
        abort_unless(config('organization.id') === 'servicios-legales-integrados', 404);

        $data = $request->validate([
            'abogado_id' => ['required', 'integer', Rule::exists('usuarios', 'id')->where('rol', 'abogado')->where('activo', true)],
            'fecha' => 'required|date_format:Y-m-d|after_or_equal:today',
            'hora_inicio' => 'required|date_format:H:i',
        ]);

        try {
            $hold = $citaService->retenerHorario(
                (int) $request->user()->id,
                (int) $data['abogado_id'],
                $data['fecha'],
                $data['hora_inicio']
            );
        } catch (BookingSchemaNotReady $e) {
            Log::warning('SLI booking schema is not ready; run scripts/migrate-organization.php in the private setup service.');
            return response()->json(['message' => $e->getMessage()], 503);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        } catch (QueryException $e) {
            if (!in_array((string) $e->getCode(), ['23505', '23000'], true)) {
                throw $e;
            }
            // La restricción UNIQUE protege también contra dos solicitudes
            // simultáneas al mismo abogado, fecha y hora.
            return response()->json(['message' => 'Ese horario acaba de ser apartado por otra persona. Elige otro.'], 409);
        }

        return response()->json([
            'token' => $hold->token,
            'expires_at' => $hold->expires_at->toIso8601String(),
            'expires_in' => 15 * 60,
        ], 201);
    }

    public function liberarHorario(Request $request, CitaService $citaService)
    {
        abort_unless(config('organization.id') === 'servicios-legales-integrados', 404);

        $data = $request->validate(['token' => 'required|uuid']);
        $citaService->liberarRetencion((int) $request->user()->id, $data['token']);

        return response()->json(['released' => true]);
    }
}
