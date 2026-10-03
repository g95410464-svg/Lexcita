<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Usuario;
use App\Models\VideoRoom;
use App\Services\CitaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JitsiConfigurationTest extends TestCase
{
    use RefreshDatabase;

    private static string $privateKey;
    private static string $publicKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setTime(12, 0));
        config(['jitsi.provider' => 'public']);
    }

    private function appointment(): array
    {
        $client = Usuario::create(['nombre' => 'Cliente', 'email' => 'cliente@example.test', 'password' => 'password', 'rol' => 'cliente', 'activo' => true]);
        $lawyer = Usuario::create(['nombre' => 'Abogado', 'email' => 'abogado@example.test', 'password' => 'password', 'rol' => 'abogado', 'activo' => true]);
        $cita = Cita::create([
            'codigo' => Cita::generarCodigo(), 'cliente_id' => $client->id, 'abogado_id' => $lawyer->id,
            'fecha' => now()->toDateString(), 'hora_inicio' => '12:00:00', 'hora_fin' => '13:00:00',
            'tipo' => 'consulta_general', 'modalidad' => 'virtual', 'descripcion' => 'Prueba privada',
            'estado' => 'pendiente_pago', 'monto' => 35,
        ]);
        app(CitaService::class)->confirmar($cita);
        return [$client, $lawyer, VideoRoom::where('cita_id', $cita->id)->sole()];
    }

    private function jaas(): void
    {
        if (!isset(self::$privateKey)) {
            $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
            openssl_pkey_export($key, $privateKey);
            self::$privateKey = $privateKey;
            self::$publicKey = openssl_pkey_get_details($key)['key'];
        }
        config(['jitsi.provider' => 'jaas', 'jitsi.jaas_app_id' => 'vpaas-magic-cookie-test',
            'jitsi.jaas_key_id' => 'vpaas-magic-cookie-test/test-key', 'jitsi.jaas_private_key' => self::$privateKey]);
    }

    private function decode(string $jwt): array
    {
        [$header, $body, $signature] = explode('.', $jwt);
        $this->assertSame(1, openssl_verify($header.'.'.$body, base64_decode(strtr($signature, '-_', '+/')), self::$publicKey, OPENSSL_ALGO_SHA256));
        $head = json_decode(base64_decode(strtr($header, '-_', '+/')), true);
        $this->assertSame('RS256', $head['alg']);
        $this->assertSame(config('jitsi.jaas_key_id'), $head['kid']);
        return json_decode(base64_decode(strtr($body, '-_', '+/')), true);
    }

    public function test_public_meetings_explain_moderator_login_and_keep_existing_room_names(): void
    {
        [$client, $lawyer, $room] = $this->appointment();
        $this->actingAs($client)->get(route('video.sala', $room->room_token))->assertOk()
            ->assertSee('espera a que tu abogado')
            ->assertViewHas('jitsi', fn ($jitsi) => $jitsi['domain'] === 'meet.jit.si' && $jitsi['jwt'] === null && $jitsi['roomName'] === $room->jitsiRoomName());
        $this->actingAs($lawyer)->get(route('video.sala', $room->room_token))->assertOk()->assertSee('Activa la sala de espera');
    }

    public function test_jaas_signs_scoped_tokens_for_both_participants_without_leaking_keys(): void
    {
        [$client, $lawyer, $room] = $this->appointment();
        $this->jaas();
        $roomNames = [];
        foreach ([$client, $lawyer] as $user) {
            $response = $this->actingAs($user)->get(route('video.sala', $room->room_token))->assertOk()
                ->assertDontSee('BEGIN PRIVATE KEY')->assertDontSee($user->email)
                ->assertDontSee('meet.jit.si')->assertHeader('Referrer-Policy', 'no-referrer');
            $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
            $settings = $response->viewData('jitsi');
            $this->assertSame('8x8.vc', $settings['domain']);
            $this->assertSame('https://8x8.vc/vpaas-magic-cookie-test/external_api.js', $settings['scriptUrl']);
            $claims = $this->decode($settings['jwt']);
            $roomNames[] = $settings['roomName'];
            $this->assertSame('vpaas-magic-cookie-test/'.$claims['room'], $settings['roomName']);
            $this->assertSame($room->jitsiRoomName(), $claims['room']);
            $this->assertFalse($claims['context']['room']['regex']);
            $this->assertSame($user->id === $lawyer->id ? 'true' : 'false', $claims['context']['user']['moderator']);
            $this->assertFalse($claims['context']['features']['recording']);
            $this->assertFalse($claims['context']['features']['transcription']);
            $this->assertSame(now()->addMinutes(30)->timestamp, $claims['exp']);
            $this->assertSame('jitsi', $claims['aud']);
            $this->assertSame('chat', $claims['iss']);
        }
        $this->assertSame($roomNames[0], $roomNames[1]);
    }

    public function test_jaas_expiration_never_exceeds_appointment_end(): void
    {
        [$client, , $room] = $this->appointment();
        $this->jaas();
        $this->travelTo(now()->setTime(12, 55));
        $response = $this->actingAs($client)->get(route('video.sala', $room->room_token))->assertOk();
        $claims = $this->decode($response->viewData('jitsi')['jwt']);
        $this->assertSame(now()->setTime(13, 0)->timestamp, $claims['exp']);
    }

    public function test_missing_or_invalid_jaas_credentials_never_fall_back_to_public_meetings(): void
    {
        [$client, , $room] = $this->appointment();
        config(['jitsi.provider' => 'jaas', 'jitsi.jaas_app_id' => null]);
        $this->actingAs($client)->get(route('video.sala', $room->room_token))->assertStatus(503)->assertDontSee('meet.jit.si');
        $this->assertDatabaseHas('video_participants', ['room_id' => $room->id, 'user_id' => $client->id, 'joined_at' => null]);
        $this->jaas();
        config(['jitsi.jaas_private_key' => 'INVALID_SECRET_KEY']);
        $this->get(route('video.sala', $room->room_token))->assertStatus(503)->assertDontSee('INVALID_SECRET_KEY');
    }

    public function test_outsiders_and_inactive_accounts_cannot_obtain_meeting_tokens(): void
    {
        [$client, , $room] = $this->appointment();
        $outsider = Usuario::create(['nombre' => 'Otro', 'email' => 'otro@example.test', 'password' => 'password', 'rol' => 'admin', 'activo' => true]);
        $this->jaas();
        $this->actingAs($outsider)->get(route('video.sala', $room->room_token))->assertForbidden();
        $client->update(['activo' => false]);
        $this->actingAs($client)->get(route('video.sala', $room->room_token))->assertForbidden();
    }

    public function test_meeting_names_and_signed_tokens_cannot_inject_scripts(): void
    {
        [$client, , $room] = $this->appointment();
        $client->update(['nombre' => '</script><script>alert(1)</script>']);
        $this->jaas();
        $this->actingAs($client)->get(route('video.sala', $room->room_token))->assertOk()
            ->assertDontSee('</script><script>alert(1)</script>', false);
    }
}
