<?php

namespace App\Services;

use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class RegistrationOtpService
{
    private const CODE_TTL_MINUTES = 10;
    private const MAX_ATTEMPTS = 5;
    private const RESEND_SECONDS = 60;

    public function begin(string $sessionId, array $data): void
    {
        $this->assertDeliveryConfigured();

        $email = Str::lower(trim($data['email']));
        $phone = $this->normalizePhone($data['telefono_whatsapp'] ?? null);
        $emailCode = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes(self::CODE_TTL_MINUTES);
        $sessionHash = hash('sha256', $sessionId);
        $legacyPhoneCodeColumn = $this->phoneCodeHashColumn();

        DB::table('registration_verifications')->where('expires_at', '<=', now())->delete();
        $emailAlreadyPending = DB::table('registration_verifications')
            ->where('email', $email)
            ->where('session_hash', '!=', $sessionHash)
            ->where('expires_at', '>', now())
            ->exists();
        if ($emailAlreadyPending) {
            throw new RuntimeException('Ya hay una verificación pendiente para ese correo. Usa el código enviado o espera a que venza.');
        }

        DB::table('registration_verifications')->updateOrInsert(
            ['session_hash' => $sessionHash],
            [
                'email' => $email,
                'registration_payload' => Crypt::encryptString(json_encode([
                    'nombre' => $data['nombre'],
                    'email' => $email,
                    'password' => $data['password'],
                    'telefono_whatsapp' => $phone,
                ], JSON_THROW_ON_ERROR)),
                'email_code_hash' => Hash::make($emailCode),
                // Kept populated for compatibility with already-deployed SLI schemas.
                // Phone verification is not part of this email-only OTP flow.
                $legacyPhoneCodeColumn => Hash::make(Str::random(40)),
                'expires_at' => $expiresAt,
                'attempts' => 0,
                'last_sent_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->sendEmailCode($email, $emailCode);
    }

    public function resend(string $sessionId): void
    {
        $this->assertDeliveryConfigured();
        $pending = $this->pending($sessionId);
        if (!$pending || now()->greaterThan(Carbon::parse($pending->expires_at))) {
            throw new RuntimeException('La verificación venció. Inicia el registro otra vez.');
        }
        if (now()->timestamp - Carbon::parse($pending->last_sent_at)->timestamp < self::RESEND_SECONDS) {
            throw new RuntimeException('Espera un minuto antes de pedir otro código.');
        }

        $emailCode = (string) random_int(100000, 999999);
        $legacyPhoneCodeColumn = $this->phoneCodeHashColumn();
        $this->sendEmailCode($pending->email, $emailCode);

        DB::table('registration_verifications')->where('id', $pending->id)->update([
            'email_code_hash' => Hash::make($emailCode),
            $legacyPhoneCodeColumn => Hash::make(Str::random(40)),
            'expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
            'attempts' => 0,
            'last_sent_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function verify(string $sessionId, string $emailCode): Usuario
    {
        $pending = $this->pending($sessionId);
        if (!$pending || now()->greaterThan(Carbon::parse($pending->expires_at))) {
            throw new RuntimeException('La verificación venció. Inicia el registro otra vez.');
        }
        if ($pending->attempts >= self::MAX_ATTEMPTS) {
            throw new RuntimeException('Se agotaron los intentos. Solicita un código nuevo.');
        }

        DB::table('registration_verifications')->where('id', $pending->id)->increment('attempts');
        if (!Hash::check($emailCode, $pending->email_code_hash)) {
            throw new RuntimeException('El código no es correcto.');
        }

        $payload = json_decode(Crypt::decryptString($pending->registration_payload), true, 512, JSON_THROW_ON_ERROR);

        return DB::transaction(function () use ($pending, $payload) {
            $locked = DB::table('registration_verifications')->where('id', $pending->id)->lockForUpdate()->first();
            if (!$locked || now()->greaterThan(Carbon::parse($locked->expires_at))) {
                throw new RuntimeException('La verificación venció. Inicia el registro otra vez.');
            }

            $usuario = Usuario::create([
                'nombre' => $payload['nombre'],
                'email' => $payload['email'],
                'password' => $payload['password'],
                'rol' => 'cliente',
                'telefono_whatsapp' => $payload['telefono_whatsapp'],
                'activo' => true,
                'email_verified_at' => now(),
                'telefono_verified_at' => null,
            ]);

            DB::table('registration_verifications')->where('id', $pending->id)->delete();
            return $usuario;
        });
    }

    public function hasPending(string $sessionId): bool
    {
        $pending = $this->pending($sessionId);
        return $pending && now()->lessThanOrEqualTo(Carbon::parse($pending->expires_at));
    }

    public function pendingContacts(string $sessionId): ?array
    {
        $pending = $this->pending($sessionId);
        if (!$pending || now()->greaterThan(Carbon::parse($pending->expires_at))) {
            return null;
        }

        [$local, $domain] = array_pad(explode('@', $pending->email, 2), 2, '');
        $maskedEmail = ($local === '' ? '' : mb_substr($local, 0, 1).str_repeat('•', min(4, max(2, mb_strlen($local) - 1))))
            .'@'.$domain;

        return [
            'email' => $maskedEmail,
            'expires_at' => Carbon::parse($pending->expires_at)->timestamp,
            'resend_at' => Carbon::parse($pending->last_sent_at)->addSeconds(self::RESEND_SECONDS)->timestamp,
        ];
    }

    private function pending(string $sessionId): ?object
    {
        return DB::table('registration_verifications')
            ->where('session_hash', hash('sha256', $sessionId))
            ->first();
    }

    private function phoneCodeHashColumn(): string
    {
        // Supports the old SMS column and the renamed WhatsApp column during SLI rollout.
        return Schema::hasColumn('registration_verifications', 'whatsapp_code_hash')
            ? 'whatsapp_code_hash'
            : 'sms_code_hash';
    }

    private function assertDeliveryConfigured(): void
    {
        if (empty(config('services.brevo.api_key'))
            || !filter_var(config('mail.from.address'), FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Falta configurar BREVO_API_KEY y un correo remitente verificado en las variables de SLI.');
        }
    }

    private function normalizePhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }
        $phone = preg_replace('/[\s().-]+/', '', trim($phone));
        if (str_starts_with($phone, '00')) {
            $phone = '+'.substr($phone, 2);
        }
        if (!str_starts_with($phone, '+') || !preg_match('/^\+[1-9]\d{7,14}$/', $phone)) {
            throw new RuntimeException('Escribe el teléfono en formato internacional, por ejemplo +50371234567.');
        }
        return $phone;
    }

    private function sendEmailCode(string $email, string $code): void
    {
        $response = Http::acceptJson()
            ->withHeaders(['api-key' => config('services.brevo.api_key')])
            ->connectTimeout(3)
            ->timeout(8)
            ->post('https://api.brevo.com/v3/smtp/email', [
                'sender' => [
                    'name' => config('mail.from.name', 'Servicios Legales Integrados'),
                    'email' => config('mail.from.address'),
                ],
                'to' => [['email' => $email]],
                'subject' => 'Código de verificación de tu cuenta SLI',
                'textContent' => "Tu código de verificación de Servicios Legales Integrados es {$code}. Vence en 10 minutos. Si no solicitaste esta cuenta, ignora este correo.",
            ]);

        if (!$response->successful()) {
            $status = $response->status();
            Log::warning('Brevo rejected SLI registration OTP email.', [
                'http_status' => $status,
                'provider_code' => $response->json('code'),
            ]);

            $message = match ($status) {
                400 => 'Brevo rechazó el remitente o los datos del correo. Verifica que MAIL_FROM_ADDRESS sea un remitente validado en Brevo.',
                401, 403 => 'Brevo rechazó la clave. Usa una API key de la sección API keys, no una clave SMTP, y revisa BREVO_API_KEY en SLI.',
                402 => 'Brevo no tiene habilitado el envío transaccional para esta cuenta o alcanzó un límite.',
                429 => 'Brevo recibió demasiadas solicitudes. Espera un momento e inténtalo otra vez.',
                default => "Brevo no aceptó el correo (HTTP {$status}). Revisa la configuración de la cuenta y del remitente.",
            };

            throw new RuntimeException($message);
        }
    }
}
