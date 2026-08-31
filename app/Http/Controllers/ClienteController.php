<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\{Cita, Usuario};
use App\Services\CitaService;
use App\Services\PayPalService;
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
            'fecha'       => 'required|date|after_or_equal:today',
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

        return redirect()->route('cliente.pre-confirmacion', $cita->id)->with('success', 'Cita agendada: ' . $cita->codigo . '. Por favor confirma el pago.');
    }

    public function misCitas()
    {
        $citas = Auth::user()->citasComoCliente()
            ->with('abogado')
            ->orderByDesc('fecha')
            ->paginate(10);

        return view('cliente.mis-citas', compact('citas'));
    }

    public function ticket(int $id)
    {
        $cita = Cita::where('id', $id)
            ->where('cliente_id', Auth::id())
            ->where('estado', 'confirmada')
            ->with(['cliente', 'abogado'])
            ->firstOrFail();

        return view('cliente.ticket', compact('cita'));
    }

    public function hacerPago(int $id)
    {
        // Wrapper de compatibilidad (Mis Citas). Deriva al checkout unificado.
        return $this->checkout($id);
    }

    public function preConfirmacion(int $id)
    {
        $cita = Cita::where('id', $id)
            ->where('cliente_id', Auth::id())
            ->where('estado', 'pendiente_pago')
            ->firstOrFail();

        return view('cliente.pre-confirmacion', compact('cita'));
    }

    public function procesarPago(int $id)
    {
        // Wrapper de compatibilidad (Dashboard). Deriva al checkout unificado.
        return $this->checkout($id);
    }

    public function paypalPago(int $id)
    {
        // Wrapper de compatibilidad (Pre-Confirmación). Deriva al checkout unificado.
        return $this->checkout($id);
    }

    /**
     * Inicia el checkout PayPal para una cita pendiente de pago del cliente.
     * Crea una orden real (Orders API v2, intent=CAPTURE), guarda el
     * paypal_order_id en la cita y redirige al enlace de aprobación de PayPal.
     * NUNCA confirma la cita aquí.
     *
     * Doble clic: PayPalService usa un PayPal-Request-Id ESTABLE (lexcita-create-{id}),
     * por lo que PayPal devuelve el MISMO Order en lugar de crear órdenes nuevas.
     */
    public function checkout(int $id)
    {
        $cita = Cita::where('id', $id)
            ->where('cliente_id', Auth::id())
            ->where('estado', 'pendiente_pago')
            ->firstOrFail();

        try {
            $result = app(PayPalService::class)->createOrder($cita);
        } catch (\Throwable $e) {
            Log::error('[PayPal] checkout: no se pudo crear orden', ['cita' => $cita->id, 'error' => $e->getMessage()]);
            return redirect()->route('cliente.pre-confirmacion', $cita->id)
                ->withErrors(['pago' => 'No se pudo iniciar el pago con PayPal. Inténtalo de nuevo.']);
        }

        $cita->update(['paypal_order_id' => $result['order_id']]);

        return redirect()->away($result['approve_url']);
    }

    /**
     * Return URL de PayPal. Captura la orden server-to-server, valida de forma
     * estricta y SOLO entonces registra el pago y confirma la cita.
     */
    public function capture(Request $request)
    {
        $orderId = $request->input('token');
        if (blank($orderId)) {
            return redirect()->route('cliente.mis-citas')
                ->withErrors(['pago' => 'No se recibió la orden de pago.']);
        }

        $cita = Cita::where('paypal_order_id', $orderId)
            ->where('cliente_id', Auth::id())
            ->first();

        if (!$cita) {
            abort(404, 'Orden de pago no encontrada.');
        }

        // Ruta rápida idempotente: pago ya registrado → sin efectos secundarios.
        if ($cita->pagoCompletado() && $cita->transaction_id) {
            if ($cita->estaConfirmada()) {
                return redirect()->route('cliente.mis-citas')
                    ->with('success', 'Tu pago ya fue procesado y tu cita está confirmada.');
            }

            // Estado INCONSISTENTE: payment_status=completed pero cita NO
            // confirmada. NO confirmar en silencio por entrar al callback:
            // registra el error y deja la decisión a un manejo explícito.
            Log::error('[PayPal] estado inconsistente: completed pero cita no confirmada', [
                'cita'   => $cita->id,
                'estado' => $cita->estado,
                'order'  => $orderId,
                'trans'  => $cita->transaction_id,
            ]);
            return redirect()->route('cliente.mis-citas')
                ->withErrors(['pago' => 'Tu pago ya fue registrado pero tu cita no quedó confirmada. Por favor contacta a soporte.']);
        }

        $result = $this->capturarYPersistir($cita, $orderId);

        if (!$result['ok']) {
            return redirect()->route('cliente.pre-confirmacion', $cita->id)
                ->withErrors(['pago' => $result['message']]);
        }

        return redirect()->route('cliente.mis-citas')
            ->with('success', 'Pago confirmado. Tu cita ' . $cita->codigo . ' ha sido confirmada.');
    }

    /**
     * Cancel URL de PayPal. NO confirma y mantiene la cita en pendiente_pago.
     */
    public function cancel(Request $request)
    {
        $orderId = $request->input('token');
        if (!blank($orderId)) {
            $cita = Cita::where('paypal_order_id', $orderId)
                ->where('cliente_id', Auth::id())
                ->first();
            if ($cita && $cita->estaPendiente()) {
                // Marca informativa del pago; el estado de la cita NO cambia.
                $cita->update(['payment_status' => 'cancelled']);
            }
        }

        return redirect()->route('cliente.mis-citas')
            ->withErrors(['pago' => 'Pago cancelado. Tu cita sigue pendiente de pago; podrás intentarlo de nuevo.']);
    }

    /**
     * Validaciones estrictas antes de confirmar. Cada una debe cumplirse.
     * Cualquier fallo devuelve false (y la cita permanece pendiente_pago).
     */
    protected function pagoValido(array $order, Cita $cita): bool
    {
        // 1. Estado de orden COMPLETED.
        if (($order['status'] ?? null) !== 'COMPLETED') {
            return false;
        }

        // 2. El Order ID capturado coincide con el guardado en la cita.
        if (($order['id'] ?? null) !== $cita->paypal_order_id) {
            return false;
        }

        $pu = $order['purchase_units'][0] ?? null;
        if (!$pu) {
            return false;
        }

        // 3. La orden pertenece a esta cita (custom_id = cita.id o invoice_id = codigo).
        $custom  = $pu['custom_id'] ?? null;
        $invoice = $pu['invoice_id'] ?? null;
        $refOk   = ($custom !== null && (string) $custom === (string) $cita->id)
                || ($invoice !== null && $invoice === $cita->codigo);
        if (!$refOk) {
            return false;
        }

        $capture = $pu['payments']['captures'][0] ?? null;
        if (!$capture) {
            return false;
        }

        // 4. Estado de la captura EXACTAMENTE COMPLETED.
        if (($capture['status'] ?? null) !== 'COMPLETED') {
            return false;
        }

        // 5. Monto capturado igual al de la cita (comparación por string).
        $esperado  = $this->normalizarMonto((string) $cita->monto);
        $capturado = $this->normalizarMonto((string) ($capture['amount']['value'] ?? ''));
        if ($capturado !== $esperado) {
            return false;
        }

        // 6. Moneda exactamente USD.
        if (($capture['amount']['currency_code'] ?? null) !== 'USD') {
            return false;
        }

        // 7. Existe un Capture ID real.
        if (blank($capture['id'] ?? null)) {
            return false;
        }

        // 8/9/10. La cita pertenece al usuario (filtro en capture()), la orden
        // coincide con la de BD (punto 2) y la idempotencia se evita en capture().
        return true;
    }

    protected function captureId(array $order): string
    {
        return $order['purchase_units'][0]['payments']['captures'][0]['id'] ?? '';
    }

    /** Normaliza un valor monetario a "N.XX" con strings, sin floats. */
    protected function normalizarMonto(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '0.00';
        }
        if (strpos($value, '.') === false) {
            $value .= '.00';
        }
        $parts = explode('.', $value, 2);
        $whole = $parts[0] === '' ? '0' : (string) (int) $parts[0];
        $cents = str_pad(substr($parts[1], 0, 2), 2, '0');
        return $whole . '.' . $cents;
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

    // ─── Helpers de dominio ────────────────────────────────────────────────

    /** Ownership-only: aborta 404 si la cita no pertenece al cliente autenticado. */
    protected function citaPropia(int $id): Cita
    {
        return Cita::where('id', $id)
            ->where('cliente_id', Auth::id())
            ->firstOrFail();
    }

    /** Ownership + estado pendiente + no pagada: usado antes de crear orden. */
    protected function citaPropiaPendiente(int $id): Cita
    {
        return Cita::where('id', $id)
            ->where('cliente_id', Auth::id())
            ->where('estado', 'pendiente_pago')
            ->where('payment_status', '!=', 'completed')
            ->firstOrFail();
    }

    // ─── Captura compartida (redirect + AJAX) ──────────────────────────────

    /**
     * Capture server-to-server + validación + persistencia bajo lock.
     * Punto único compartido por el callback GET (redirect) y el endpoint
     * AJAX del JS SDK. NO invocado desde crearOrdenAjax (create ≠ capture).
     *
     * Devuelve ['ok' => bool, 'message' => string].
     */
    protected function capturarYPersistir(Cita $cita, string $orderId): array
    {
        try {
            $order = app(PayPalService::class)->captureOrder($orderId);
        } catch (\Throwable $e) {
            Log::error('[PayPal] capture: error al capturar', ['cita' => $cita->id, 'order' => $orderId, 'error' => $e->getMessage()]);
            return ['ok' => false, 'message' => 'No se pudo completar el pago. Tu cita sigue pendiente; inténtalo de nuevo.'];
        }

        if (!$this->pagoValido($order, $cita)) {
            Log::warning('[PayPal] capture: validaciones fallidas', ['cita' => $cita->id, 'order' => $orderId]);
            return ['ok' => false, 'message' => 'El pago no pudo validarse. Tu cita sigue pendiente de pago.'];
        }

        // Persistir bajo lock: se vuelve a leer la cita bloqueada; si otro
        // request concurrente completó mientras capturábamos, NO repetimos
        // efectos secundarios (no confirma dos veces ni duplica VideoRoom).
        DB::transaction(function () use ($cita, $order, $orderId) {
            $locked = Cita::where('paypal_order_id', $orderId)
                ->where('cliente_id', $cita->cliente_id)
                ->lockForUpdate()
                ->first();

            // Fila borrada o ya procesada por otro request → sin efectos.
            if (!$locked || ($locked->pagoCompletado() && $locked->transaction_id)) {
                return;
            }

            $locked->update([
                'payment_status'   => 'completed',
                'paypal_order_id'  => $orderId,
                'transaction_id'   => $this->captureId($order),
                'paid_at'          => now(),
            ]);

            app(CitaService::class)->confirmar($locked);
        });

        return ['ok' => true, 'message' => 'Pago confirmado.'];
    }

    // ─── Endpoints AJAX (JS SDK inline) ────────────────────────────────────

    /**
     * POST /cliente/paypal/create/{id}
     * Crea una orden PayPal real (Orders API v2) y devuelve {orderID}
     * para que el JS SDK abra el popup de PayPal. NO confirma la cita.
     */
    public function crearOrdenAjax(int $id)
    {
        $cita = $this->citaPropiaPendiente($id);

        try {
            $result = app(PayPalService::class)->createOrder($cita);
        } catch (\Throwable $e) {
            Log::error('[PayPal] ajax create: no se pudo crear orden', ['cita' => $cita->id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'No se pudo iniciar el pago con PayPal. Inténtalo de nuevo.',
            ], 502);
        }

        $cita->update(['paypal_order_id' => $result['order_id']]);

        return response()->json([
            'success' => true,
            'orderID' => $result['order_id'],
        ]);
    }

    /**
     * POST /cliente/paypal/capture/{id}
     * Captura la orden, valida y confirma la cita. Punto AJAX equivalente
     * al callback GET capture(), compartiendo la misma lógica de validación
     * y persistencia a través de capturarYPersistir().
     */
    public function capturarOrdenAjax(Request $request, int $id)
    {
        $cita = $this->citaPropia($id);

        // Idempotencia: pago ya registrado → respuesta exitosa sin re-procesar.
        if ($cita->pagoCompletado() && $cita->transaction_id) {
            if ($cita->estaConfirmada()) {
                return response()->json([
                    'success'  => true,
                    'message'  => 'Pago ya procesado.',
                    'redirect' => route('cliente.mis-citas'),
                ]);
            }

            Log::error('[PayPal] ajax capture: estado inconsistente', [
                'cita'   => $cita->id,
                'estado' => $cita->estado,
                'trans'  => $cita->transaction_id,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Tu pago ya fue registrado pero tu cita no quedó confirmada. Contacta soporte.',
            ], 409);
        }

        $orderId = (string) $request->input('orderID', '');

        if (blank($orderId)) {
            return response()->json([
                'success' => false,
                'message' => 'No se recibió la orden de pago.',
            ], 422);
        }

        if ($orderId !== $cita->paypal_order_id) {
            Log::warning('[PayPal] ajax capture: orderID no coincide', ['cita' => $cita->id, 'orderId' => $orderId]);
            return response()->json([
                'success' => false,
                'message' => 'La orden de pago no corresponde a esta cita.',
            ], 422);
        }

        $result = $this->capturarYPersistir($cita, $orderId);

        if (!$result['ok']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 422);
        }

        return response()->json([
            'success'  => true,
            'message'  => $result['message'],
            'redirect' => route('cliente.mis-citas'),
        ]);
    }
}