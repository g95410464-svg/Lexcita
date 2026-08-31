<?php

namespace App\Services;

use App\Models\Cita;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Encapsula la comunicación con la API REST de PayPal (Orders API v2).
 *
 * Responsabilidades: autenticación OAuth2 (access token), creación de orden
 * (intent=CAPTURE) y captura server-to-server. NO guarda secretos hardcodeados:
 * las credenciales provienen de config('services.paypal').
 *
 * Este servicio NO confirma citas ni crea VideoRooms: solo habla con PayPal.
 */
class PayPalService
{
    /** Access token en memoria para reutilizarlo dentro del mismo request. */
    protected ?string $accessToken = null;

    protected function config(): array
    {
        return config('services.paypal');
    }

    protected function clientId(): string
    {
        $id = $this->config()['client_id'] ?? null;
        if (blank($id)) {
            throw new RuntimeException('PayPalService: PAYPAL_CLIENT_ID no configurado.');
        }
        return $id;
    }

    protected function secret(): string
    {
        $secret = $this->config()['secret'] ?? null;
        if (blank($secret)) {
            throw new RuntimeException('PayPalService: PAYPAL_SECRET no configurado.');
        }
        return $secret;
    }

    protected function baseUri(): string
    {
        return $this->config()['base_uri'];
    }

    protected function timeout(): int
    {
        return (int) ($this->config()['timeout'] ?? 15);
    }

    /** HTTP client común con Accept json, timeout y logging de errores. */
    protected function client(array $headers = []): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withHeaders(array_merge(['Accept' => 'application/json'], $headers))
            ->acceptJson()
            ->timeout($this->timeout());
    }

    /**
     * Obtiene un access token OAuth2 (Client Credentials).
     * Se memoiza en memoria dentro del ciclo de vida del request.
     */
    public function getAccessToken(): string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->clientId() . ':' . $this->secret()),
                'Accept'        => 'application/json',
            ])
                ->asForm()
                ->timeout($this->timeout())
                ->post($this->baseUri() . '/v1/oauth2/token', [
                    'grant_type' => 'client_credentials',
                ]);
        } catch (\Throwable $e) {
            Log::error('[PayPal] token: error de red', ['error' => $e->getMessage()]);
            throw new RuntimeException('No se pudo obtener token de PayPal.', 0, $e);
        }

        if ($response->failed()) {
            Log::error('[PayPal] token: respuesta HTTP ' . $response->status(), [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new RuntimeException('PayPal no autorizó la autenticación.');
        }

        $token = $response->json('access_token');
        if (blank($token)) {
            throw new RuntimeException('PayPal no devolvió access_token.');
        }

        $this->accessToken = $token;
        return $token;
    }

    /**
     * Crea una orden PayPal (intent=CAPTURE) con el monto de la cita obtenido
     * de la BD (nunca del navegador). Devuelve ['order_id'=>..., 'approve_url'=>...].
     */
    public function createOrder(Cita $cita): array
    {
        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => (string) $cita->id,
                    'custom_id'    => (string) $cita->id,
                    'invoice_id'   => $cita->codigo,
                    'amount'       => [
                        'currency_code' => config('services.paypal.currency', 'USD'),
                        'value'         => $this->montoComoString($cita),
                    ],
                ],
            ],
            'application_context' => [
                'shipping_preference' => 'NO_SHIPPING',
                'user_action'         => 'PAY_NOW',
                'return_url'          => route('cliente.paypal.return'),
                'cancel_url'          => route('cliente.paypal.cancel'),
            ],
        ];

        try {
            // PayPal-Request-Id idempotente y ESTABLE por cita: si el doble clic
            // (o un retry) vuelve a crear la misma operación lógica, PayPal
            // devuelve la MISMA orden en lugar de crear órdenes descontroladas.
            $response = $this->client([
                'Authorization'     => 'Bearer ' . $this->getAccessToken(),
                'PayPal-Request-Id' => 'lexcita-create-' . $cita->id,
            ])
                ->post($this->baseUri() . '/v2/checkout/orders', $payload);
        } catch (\Throwable $e) {
            Log::error('[PayPal] createOrder: error', ['cita' => $cita->id, 'error' => $e->getMessage()]);
            throw new RuntimeException('No se pudo crear la orden de pago.', 0, $e);
        }

        if ($response->failed()) {
            Log::error('[PayPal] createOrder: respuesta HTTP ' . $response->status(), [
                'cita'   => $cita->id,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new RuntimeException('PayPal rechazó la creación de la orden.');
        }

        $orderId = $response->json('id');
        $approve = null;
        foreach (($response->json('links') ?? []) as $link) {
            if (($link['rel'] ?? null) === 'approve') {
                $approve = $link['href'] ?? null;
                break;
            }
        }

        if (blank($orderId) || blank($approve)) {
            Log::error('[PayPal] createOrder: falta order id o enlace approve', [
                'cita' => $cita->id,
                'body' => $response->body(),
            ]);
            throw new RuntimeException('PayPal no devolvió un enlace de aprobación.');
        }

        return ['order_id' => $orderId, 'approve_url' => $approve];
    }

    /**
     * Captura una orden aprobada. Devuelve el JSON decodificado de la respuesta.
     */
    public function captureOrder(string $orderId): array
    {
        try {
            // PayPal-Request-Id estable para la captura: si Laravel recibe un
            // timeout después de que PayPal ya capturó el dinero y reintenta,
            // PayPal reconoce el MISMO intento lógico y no genera una segunda
            // operación. md5 mantiene el id en formato/longitud aceptados.
            $requestId = 'lexcita-capture-' . md5($orderId);

            $response = $this->client([
                'Authorization'     => 'Bearer ' . $this->getAccessToken(),
                'PayPal-Request-Id' => $requestId,
            ])
                ->post($this->baseUri() . '/v2/checkout/orders/' . $orderId . '/capture', []);
        } catch (\Throwable $e) {
            Log::error('[PayPal] captureOrder: error de red', ['order' => $orderId, 'error' => $e->getMessage()]);
            throw new RuntimeException('No se pudo capturar el pago.', 0, $e);
        }

        if ($response->failed()) {
            Log::error('[PayPal] captureOrder: respuesta HTTP ' . $response->status(), [
                'order'  => $orderId,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new RuntimeException('PayPal no pudo capturar el pago.');
        }

        $data = $response->json();
        if (!is_array($data)) {
            throw new RuntimeException('PayPal devolvió una respuesta inválida.');
        }

        return $data;
    }

    /** Valor del monto como string de 2 decimales ("35.00"). Sin floats inseguros. */
    protected function montoComoString(Cita $cita): string
    {
        // $cita->monto es decimal cast → string "35.00"; normalizamos por seguridad.
        return $this->normalizarMonto((string) $cita->monto);
    }

    /** Normaliza un valor monetario a "N.XX" trabajando con strings, no floats. */
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
}
