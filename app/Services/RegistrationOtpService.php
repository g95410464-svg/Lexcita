<?php

namespace App\Services;

use App\Models\Usuario;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;
use RuntimeException;

class RegistrationOtpService
{
    private const CODE_TTL_MINUTES = 10;
    private const MAX_ATTEMPTS = 5;
    private const RESEND_SECONDS = 60;

    public function begin(string $sessionId, array $data): void
    {
        $this->assertProvidersConfigured();

        $email = Str::lower(trim($data['email']));
        $phone = $this->normalizePhone($data['telefono_whatsapp']);
        $emailCode = (string) random_int(100000, 999999);
        $smsCode = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes(self::CODE_TTL_MINUTES);
        $sessionHash = hash('sha256', $sessionId);
        DB::table('registration_verifications')->where('expires_at', '<=', now())->delete();
        $emailAlreadyPending = DB::table('registration_verifications')
            ->where('email', $email)
            ->where('session_hash', '!=', $sessionHash)
            ->where('expires_at', '>', now())
            ->exists();
        if ($emailAlreadyPending) {
            throw new RuntimeException('Ya hay una verificación pendiente para ese correo. Usa los códigos enviados o espera a que venzan.');
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
                'sms_code_hash' => Hash::make($smsCode),
                'expires_at' => $expiresAt,
                'attempts' => 0,
                'last_sent_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->sendEmailCode($email, $emailCode);
        $this->sendSmsCode($phone, $smsCode);
    }

    public function resend(string $sessionId): void
    {
        $this->assertProvidersConfigured();
        $pending = $this->pending($sessionId);
        if (!$pending || now()->greaterThan(Carbon::parse($pending->expires_at))) {
            throw new RuntimeException('La verificación venció. Inicia el registro otra vez.');
        }
        if (now()->timestamp - Carbon::parse($pending->last_sent_at)->timestamp < self::RESEND_SECONDS) {
            throw new RuntimeException('Espera un minuto antes de pedir otros códigos.');
        }

        $emailCode = (string) random_int(100000, 999999);
        $smsCode = (string) random_int(100000, 999999);
        $payload = json_decode(Crypt::decryptString($pending->registration_payload), true, 512, JSON_THROW_ON_ERROR);
        DB::table('registration_verifications')->where('id', $pending->id)->update([
            'email_code_hash' => Hash::make($emailCode),
            'sms_code_hash' => Hash::make($smsCode),
            'expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
            'attempts' => 0,
            'last_sent_at' => now(),
            'updated_at' => now(),
        ]);

        $this->sendEmailCode($pending->email, $emailCode);
        $this->sendSmsCode($payload['telefono_whatsapp'], $smsCode);
    }

    public function verify(string $sessionId, string $emailCode, string $smsCode): Usuario
    {
        $this->assertProvidersConfigured();
        $pending = $this->pending($sessionId);
        if (!$pending || now()->greaterThan(Carbon::parse($pending->expires_at))) {
            throw new RuntimeException('La verificación venció. Inicia el registro otra vez.');
        }
        if ($pending->attempts >= self::MAX_ATTEMPTS) {
            throw new RuntimeException('Se agotaron los intentos. Solicita códigos nuevos.');
        }

        DB::table('registration_verifications')->where('id', $pending->id)->increment('attempts');
        if (!Hash::check($emailCode, $pending->email_code_hash)
            || !Hash::check($smsCode, $pending->sms_code_hash)) {
            throw new RuntimeException('Uno o ambos códigos no son correctos.');
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
                'telefono_verified_at' => now(),
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

        $payload = json_decode(Crypt::decryptString($pending->registration_payload), true, 512, JSON_THROW_ON_ERROR);
        [$local, $domain] = array_pad(explode('@', $pending->email, 2), 2, '');
        $maskedEmail = ($local === '' ? '' : mb_substr($local, 0, 1).str_repeat('•', min(4, max(2, mb_strlen($local) - 1))))
            .'@'.$domain;
        $digits = preg_replace('/\D+/', '', $payload['telefono_whatsapp']);
        $prefix = '+'.substr($digits, 0, min(3, max(1, strlen($digits) - 4)));
        $maskedPhone = $prefix.' ••• •• '.substr($digits, -4);

        return ['email' => $maskedEmail, 'phone' => $maskedPhone];
    }

    private function pending(string $sessionId): ?object
    {
        return DB::table('registration_verifications')
            ->where('session_hash', hash('sha256', $sessionId))
            ->first();
    }

    private function assertProvidersConfigured(): void
    {
        $twilio = config('services.twilio');
        $mailer = config('mail.default');
        if (empty($twilio['account_sid']) || empty($twilio['auth_token']) || empty($twilio['sms_from'])) {
            throw new RuntimeException('Falta configurar el número emisor SMS de Twilio en SLI.');
        }
        if (!$mailer || in_array($mailer, ['log', 'array'], true)) {
            throw new RuntimeException('Falta configurar un servicio de correo real para enviar códigos OTP.');
        }
    }

    private function normalizePhone(string $phone): string
    {
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
        Mail::raw("Tu código de verificación de Servicios Legales Integrados es {$code}. Vence en 10 minutos. Si no solicitaste esta cuenta, ignora este correo.", function ($message) use ($email) {
            $message->to($email)->subject('Código de verificación de tu cuenta SLI');
        });
    }

    private function sendSmsCode(string $phone, string $code): void
    {
        $config = config('services.twilio');
        if (!preg_match('/^\\+[1-9]\\d{7,14}$/', $config['sms_from'])) {
            throw new RuntimeException('El número emisor SMS de Twilio debe estar en formato internacional.');
        }

        $response = Http::asForm()
            ->withBasicAuth($config['account_sid'], $config['auth_token'])
            ->connectTimeout(5)
            ->timeout(10)
            ->post('https://api.twilio.com/2010-04-01/Accounts/'.rawurlencode($config['account_sid']).'/Messages.json', [
                'To' => $phone,
                'From' => $config['sms_from'],
                'Body' => "SLI: Tu codigo es {$code}. Vence en 10 minutos. No lo compartas.",
            ]);

        if (!$response->successful() || !$response->json('sid')) {
            throw new RuntimeException('Twilio no pudo enviar el código SMS. Revisa el emisor y los permisos de mensajería.');
        }
    }
}
