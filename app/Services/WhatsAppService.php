<?php

namespace App\Services;

use App\Models\Cita;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $accountSid;
    private string $authToken;
    private string $fromNumber;

    public function __construct()
    {
        $this->accountSid = config('services.twilio.account_sid', '');
        $this->authToken  = config('services.twilio.auth_token', '');
        $this->fromNumber = config('services.twilio.whatsapp_from', 'whatsapp:+14155238886');
    }

    public function enviarConfirmacion(Cita $cita): void
    {
        $cita->load(['cliente', 'abogado']);

        $mensaje = "✅ *Cita CONFIRMADA — LexCita*\n\n"
            . "📋 Código: {$cita->codigo}\n"
            . "👨‍⚖️ Abogado: {$cita->abogado->nombre}\n"
            . "📅 Fecha: " . $cita->fecha->format('d/m/Y') . "\n"
            . "🕐 Hora: {$cita->hora_inicio}\n"
            . "📍 Modalidad: " . ucfirst($cita->modalidad) . "\n\n"
            . "Gracias por usar LexCita.";

        $this->enviar($cita->cliente->telefono_whatsapp, $mensaje);
    }

    public function enviarRecordatorio(Cita $cita): void
    {
        $cita->load(['cliente', 'abogado']);

        $mensaje = "⏰ *Recordatorio — LexCita*\n\n"
            . "Tu cita es *mañana* " . $cita->fecha->format('d/m/Y')
            . " a las {$cita->hora_inicio}\n"
            . "con {$cita->abogado->nombre}.\n\n"
            . "Código: {$cita->codigo}";

        $this->enviar($cita->cliente->telefono_whatsapp, $mensaje);
    }

    private function enviar(string $telefono, string $mensaje): void
    {
        if (empty($this->accountSid) || empty($this->authToken)) {
            Log::info('[WhatsApp] Credenciales no configuradas. Mensaje simulado: ' . $mensaje);
            return;
        }

        $to = 'whatsapp:+' . preg_replace('/\D/', '', $telefono);

        try {
            Http::withBasicAuth($this->accountSid, $this->authToken)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json", [
                    'From' => $this->fromNumber,
                    'To'   => $to,
                    'Body' => $mensaje,
                ]);
        } catch (\Exception $e) {
            Log::error('[WhatsApp] Error al enviar: ' . $e->getMessage());
        }
    }
}
