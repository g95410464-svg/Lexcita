<?php

namespace App\Services;

use App\Models\{Usuario, HorarioDisponible};

class HorarioService
{
    /**
     * Crea horarios Lun-Vie 08:00-17:00 por defecto para un abogado nuevo.
     */
    public function crearHorariosDefault(Usuario $abogado): void
    {
        $dias = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];

        foreach ($dias as $dia) {
            HorarioDisponible::firstOrCreate(
                ['abogado_id' => $abogado->id, 'dia_semana' => $dia],
                ['hora_inicio' => '08:00:00', 'hora_fin' => '17:00:00', 'activo' => true]
            );
        }
    }
}
