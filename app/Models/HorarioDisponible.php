<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HorarioDisponible extends Model
{
    protected $table = 'horarios_disponibles';

    protected $fillable = [
        'abogado_id', 'dia_semana', 'hora_inicio', 'hora_fin', 'activo',
    ];

    protected $casts = ['activo' => 'boolean'];

    public function abogado() { return $this->belongsTo(Usuario::class, 'abogado_id'); }

    // Días de la semana en español → número PHP (1=lunes ... 5=viernes)
    public static function diaSemanaMap(): array
    {
        return [
            'lunes'     => 1,
            'martes'    => 2,
            'miercoles' => 3,
            'jueves'    => 4,
            'viernes'   => 5,
        ];
    }
}
