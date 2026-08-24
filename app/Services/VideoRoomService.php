<?php

namespace App\Services;

use App\Models\{Cita, VideoRoom, VideoParticipant, VideoMessage, VideoFile, ConsultationNote, Usuario};
use Carbon\Carbon;
use Illuminate\Support\Str;

class VideoRoomService
{
    /**
     * Obtiene o crea la sala de video para una cita virtual confirmada.
     * Si no existe, la crea y registra a ambos participantes.
     */
    public function obtenerOCrearSala(Cita $cita): VideoRoom
    {
        if (!$cita->esVirtual()) {
            throw new \InvalidArgumentException('La cita no es virtual.');
        }

        if (!$cita->estaConfirmada()) {
            throw new \InvalidArgumentException('La cita debe estar confirmada.');
        }

        if ($cita->videoRoom) {
            return $cita->videoRoom->fresh(['participantes.usuario']);
        }

        return $this->crearSala($cita);
    }

    /**
     * Crea la sala de video y registra participantes.
     */
    public function crearSala(Cita $cita): VideoRoom
    {
        $roomToken = Str::random(40);

        $videoRoom = VideoRoom::create([
            'cita_id'    => $cita->id,
            'room_token' => $roomToken,
            'status'     => 'programada',
        ]);

        // Registrar a ambos participantes
        VideoParticipant::create([
            'room_id' => $videoRoom->id,
            'user_id' => $cita->cliente_id,
        ]);

        VideoParticipant::create([
            'room_id' => $videoRoom->id,
            'user_id' => $cita->abogado_id,
        ]);

        return $videoRoom->fresh(['participantes.usuario']);
    }

    /**
     * Marca la sala como disponible (15 min antes de la cita).
     * Se puede llamar via scheduler o al primer intento de entrada.
     */
    public function marcarDisponible(VideoRoom $room): VideoRoom
    {
        if ($room->estaProgramada()) {
            $room->marcarDisponible();
        }
        return $room->fresh();
    }

    /**
     * Registra la entrada de un participante a la sala.
     * Cambia estado a 'en_espera' si es el primero, 'en_consulta' si ya hay otro.
     */
    public function registrarEntrada(VideoRoom $room, Usuario $usuario): VideoParticipant
    {
        $participante = $room->participantes()
            ->where('user_id', $usuario->id)
            ->firstOrFail();

        if (!$participante->estaConectado()) {
            $participante->registrarEntrada();

            // Actualizar estado de la sala según cuántos estén conectados
            $conectados = $room->participantes()->whereNull('left_at')->count();

            if ($conectados === 1) {
                $room->marcarEnEspera();
            } elseif ($conectados >= 2) {
                $room->marcarEnConsulta();
            }
        }

        return $participante->fresh();
    }

    /**
     * Registra la salida de un participante.
     */
    public function registrarSalida(VideoRoom $room, Usuario $usuario): void
    {
        $participante = $room->participantes()
            ->where('user_id', $usuario->id)
            ->whereNull('left_at')
            ->first();

        if ($participante) {
            $participante->registrarSalida();

            // Si ya no hay nadie conectado y la sala estaba en consulta, finalizar
            $conectados = $room->participantes()->whereNull('left_at')->count();
            if ($conectados === 0 && $room->estaEnConsulta()) {
                $room->marcarFinalizada();
            }
        }
    }

    /**
     * Guarda un mensaje del chat en la sala.
     */
    public function guardarMensaje(VideoRoom $room, Usuario $usuario, string $mensaje): VideoMessage
    {
        return VideoMessage::create([
            'room_id' => $room->id,
            'user_id' => $usuario->id,
            'message' => $mensaje,
        ]);
    }

    /**
     * Obtiene mensajes recientes de la sala.
     */
    public function obtenerMensajes(VideoRoom $room, int $limite = 50): \Illuminate\Database\Eloquent\Collection
    {
        return $room->mensajes()
            ->with('usuario')
            ->latest()
            ->take($limite)
            ->get()
            ->reverse();
    }

    /**
     * Guarda un archivo compartido en la sala.
     * El archivo debe estar ya guardado en storage/app/private/video-files/{room_token}/
     */
    public function guardarArchivo(VideoRoom $room, Usuario $usuario, string $fileName, string $filePath, ?string $mimeType = null): VideoFile
    {
        return VideoFile::create([
            'room_id'   => $room->id,
            'user_id'   => $usuario->id,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'mime_type' => $mimeType,
        ]);
    }

    /**
     * Obtiene archivos compartidos en la sala.
     */
    public function obtenerArchivos(VideoRoom $room): \Illuminate\Database\Eloquent\Collection
    {
        return $room->archivos()->with('usuario')->latest()->get();
    }

    /**
     * Crea o actualiza notas privadas del abogado.
     * Solo el abogado de la cita puede crear/ver estas notas.
     */
    public function guardarNota(Cita $cita, Usuario $abogado, string $notas): ConsultationNote
    {
        // Validar que el usuario es el abogado de la cita
        if ($cita->abogado_id !== $abogado->id) {
            throw new \AuthorizationException('Solo el abogado de la cita puede guardar notas.');
        }

        return ConsultationNote::updateOrCreate(
            ['cita_id' => $cita->id, 'abogado_id' => $abogado->id],
            ['notes' => $notas]
        );
    }

    /**
     * Obtiene la nota privada del abogado para una cita.
     * Solo el abogado de la cita puede verla.
     */
    public function obtenerNota(Cita $cita, Usuario $usuario): ?ConsultationNote
    {
        if ($cita->abogado_id !== $usuario->id) {
            return null;
        }

        return $cita->consultationNote()
            ->where('abogado_id', $usuario->id)
            ->first();
    }

    /**
     * Verifica si un usuario puede acceder a la sala.
     * Validaciones: autenticado, pertenece a la cita, cita confirmada, virtual, ventana de tiempo.
     */
    public function validarAcceso(VideoRoom $room, Usuario $usuario): array
    {
        $cita = $room->cita;

        // 1. Usuario autenticado (ya validado por middleware auth)
        // 2. Usuario pertenece a la cita
        if ($cita->cliente_id !== $usuario->id && $cita->abogado_id !== $usuario->id) {
            return [false, 'No perteneces a esta cita.'];
        }

        // 3. Cita en estado confirmada
        if (!$cita->estaConfirmada()) {
            return [false, 'La cita no está confirmada.'];
        }

        // 4. Modalidad virtual
        if (!$cita->esVirtual()) {
            return [false, 'Esta cita no es virtual.'];
        }

        // 5. Dentro de la ventana de tiempo permitida
        if (!$cita->ventanaVideollamadaAbierta()) {
            return [false, 'Fuera del horario permitido para la videollamada (15 min antes hasta el fin de la cita).'];
        }

        return [true, 'Acceso permitido.'];
    }

    /**
     * Verifica si ambos participantes han dado consentimiento.
     */
    public function ambosConsintieron(Cita $cita): bool
    {
        return $cita->ambosConsintieron();
    }

    /**
     * Registra consentimiento del cliente.
     */
    public function consentirCliente(Cita $cita): void
    {
        $cita->registrarConsentimientoCliente();
    }

    /**
     * Registra consentimiento del abogado.
     */
    public function consentirAbogado(Cita $cita): void
    {
        $cita->registrarConsentimientoAbogado();
    }

    /**
     * Finaliza la consulta manualmente (solo abogado).
     * Cambia estado a 'finalizada' y registra salida de todos.
     */
    public function finalizarConsulta(VideoRoom $room, Usuario $abogado): void
    {
        $cita = $room->cita;

        if ($cita->abogado_id !== $abogado->id) {
            throw new \AuthorizationException('Solo el abogado puede finalizar la consulta.');
        }

        // Registrar salida de todos los participantes conectados
        $conectados = $room->participantes()->whereNull('left_at')->get();
        foreach ($conectados as $p) {
            $p->registrarSalida();
        }

        $room->marcarFinalizada();
    }

    /**
     * Obtiene datos para la vista de la sala (video + chat + archivos + notas).
     */
    public function obtenerDatosSala(VideoRoom $room, Usuario $usuario): array
    {
        $cita = $room->cita->load(['cliente', 'abogado']);
        $esAbogado = $cita->abogado_id === $usuario->id;

        return [
            'room'           => $room->load(['participantes.usuario']),
            'cita'           => $cita,
            'esAbogado'      => $esAbogado,
            'mensajes'       => $this->obtenerMensajes($room),
            'archivos'       => $this->obtenerArchivos($room),
            'notaAbogado'    => $esAbogado ? $this->obtenerNota($cita, $usuario) : null,
            'ventanaAbierta' => $cita->ventanaVideollamadaAbierta(),
            'tiempoRestante' => $this->calcularTiempoRestante($cita),
        ];
    }

    /**
     * Calcula segundos restantes hasta el fin de la cita.
     */
    private function calcularTiempoRestante(Cita $cita): int
    {
        $fin = Carbon::parse($cita->fecha->format('Y-m-d') . ' ' . $cita->hora_fin);
        return max(0, now()->diffInSeconds($fin, false));
    }

    /**
     * Obtiene configuración STUN para WebRTC.
     */
    public function getStunConfig(): array
    {
        return [
            'iceServers' => [
                ['urls' => 'stun:stun.l.google.com:19302'],
                ['urls' => 'stun:stun1.l.google.com:19302'],
                ['urls' => 'stun:stun2.l.google.com:19302'],
            ],
        ];
    }
}