<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingHold extends Model
{
    protected $table = 'booking_holds';

    protected $fillable = [
        'token', 'cliente_id', 'abogado_id', 'fecha', 'hora_inicio', 'expires_at',
    ];

    protected $casts = [
        'fecha' => 'date',
        'expires_at' => 'datetime',
    ];
}
