<?php

namespace App\Services;

use App\Models\Usuario;
use App\Models\VideoRoom;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Log;

class JitsiMeetingService
{
    public function forParticipant(VideoRoom $room, Usuario $user): array
    {
        [$allowed, $message] = app(VideoRoomService::class)->validarAcceso($room, $user);
        abort_unless($allowed && $user->activo, 403, $message);

        $provider = config('jitsi.provider');
        if ($provider === 'public') {
            return [
                'provider' => 'public',
                'domain' => 'meet.jit.si',
                'scriptUrl' => 'https://meet.jit.si/external_api.js',
                'roomName' => $room->jitsiRoomName(),
                'jwt' => null,
            ];
        }

        $appId = (string) config('jitsi.jaas_app_id');
        $keyId = (string) config('jitsi.jaas_key_id');
        $privateKey = str_replace('\\n', "\n", (string) config('jitsi.jaas_private_key'));
        abort_unless($provider === 'jaas'
            && preg_match('/^vpaas-magic-cookie-[a-zA-Z0-9]+$/D', $appId)
            && str_starts_with($keyId, $appId.'/')
            && strlen($keyId) > strlen($appId) + 1
            && filled($privateKey), 503, 'La videollamada privada todavía no está configurada.');

        $end = Carbon::parse($room->cita->fecha->format('Y-m-d').' '.$room->cita->hora_fin)->timestamp;
        $expires = min(now()->addMinutes(30)->timestamp, $end);
        abort_if($expires <= now()->timestamp, 403, 'La videollamada está fuera del horario permitido.');
        $claims = [
            'aud' => 'jitsi',
            'iss' => 'chat',
            'sub' => $appId,
            'iat' => now()->timestamp,
            'nbf' => now()->subSeconds(10)->timestamp,
            'exp' => $expires,
            'room' => $room->jitsiRoomName(),
            'context' => [
                'room' => ['regex' => false],
                'user' => [
                    'id' => hash_hmac('sha256', (string) $user->id, config('app.key')),
                    'name' => $user->nombre,
                    'moderator' => $room->cita->abogado_id === $user->id ? 'true' : 'false',
                ],
                'features' => [
                    'recording' => false,
                    'livestreaming' => false,
                    'transcription' => false,
                    'inbound-call' => false,
                    'outbound-call' => false,
                    'sip-inbound-call' => false,
                    'sip-outbound-call' => false,
                    'file-upload' => false,
                ],
            ],
        ];

        try {
            $jwt = JWT::encode($claims, $privateKey, 'RS256', $keyId);
        } catch (\Exception $exception) {
            Log::warning('JaaS token signing failed.', ['exception_type' => get_class($exception)]);
            abort(503, 'La videollamada privada no está disponible. Contacta con la administración.');
        }

        return [
            'provider' => 'jaas',
            'domain' => '8x8.vc',
            'scriptUrl' => 'https://8x8.vc/'.$appId.'/external_api.js',
            'roomName' => $appId.'/'.$room->jitsiRoomName(),
            'jwt' => $jwt,
        ];
    }
}
