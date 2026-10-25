<?php

namespace App\Services\Agenda;

use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

class CalendarLock
{
    // Reuse the reservation/payment lock. One lawyer row also orders writes
    // across midnight and across several days, without introducing deadlocks.
    public function run(int $lawyerId, callable $operation): mixed
    {
        return DB::transaction(function () use ($lawyerId, $operation) {
            Usuario::whereKey($lawyerId)->where('rol', 'abogado')->where('activo', true)
                ->lockForUpdate()->firstOrFail();
            return $operation();
        }, 3);
    }
}
