<?php

namespace App\Services;

use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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
        $this->assertMailConfigured();

        $email = Str::lower(trim($data['email']));
        $phone = $this->normalizePhone($data['telefono_whatsapp']);
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
        $this->assertMailConfigured();
        $pending = $this->pending($sessionId);
        if (!$pending || now()->greaterThan(Carbon::parse($pending->expires_at))) {
            throw new RuntimeException('La verificación venció. Inicia el registro otra vez.');
        }
        if (now()->timestamp - Carbon::parse($pending->last_sent_at)->timestamp < self::RESEND_SECONDS) {
            throw new RuntimeException('Espera un minuto antes de pedir otro código.');
        }

        $emailCode = (string) random_int(100000, 999999);
        $legacyPhoneCodeColumn = $this->phoneCodeHashColumn();
        DB::table('registration_verifications')->where('id', $pending->id)->update([
            'email_code_hash' => Hash::make($emailCode),
            $legacyPhoneCodeColumn => Hash::make(Str::random(40)),
            'expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
            'attempts' => 0,
            'last_sent_at' => now(),
            'updated_at' => now(),
        ]);

        $this->sendEmailCode($pending->email, $emailCode);
    }

    public function verify(string $sessionId, string $emailCode): Usuario
    {
        $this->assertMailConfigured();
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

    private function assertMailConfigured(): void
    {
        $smtp = config('mail.mailers.smtp');
        if (config('mail.default') !== 'smtp'
            || empty($smtp['host'])
            || empty($smtp['port'])
            || empty($smtp['username'])
            || empty($smtp['password'])
            || !filter_var(config('mail.from.address'), FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Faltan las credenciales SMTP de Brevo en las variables MAIL_* de SLI.');
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
        Mail::raw(
            "Tu código de verificación de Servicios Legales Integrados es {$code}. Vence en 10 minutos. Si no solicitaste esta cuenta, ignora este correo.",
            function ($message) use ($email) {
                $message->to($email)->subject('Código de verificación de tu cuenta SLI');
            }
        );
    }
}
