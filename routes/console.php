<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('agenda:review', function () {
    if (!app(\App\Support\AgendaModule::class)->ready()) { $this->comment('Agenda no activada o pendiente de migración.'); return; }
    foreach (\App\Models\Usuario::where('rol','abogado')->where('activo',true)->pluck('id') as $lawyer) {
        app(\App\Services\Agenda\CalendarLock::class)->run($lawyer,
            fn()=>app(\App\Services\Agenda\AgendaConflictService::class)->review($lawyer));
    }
    $this->info('Revisión completada. Ninguna cita se mueve ni se cobra automáticamente.');
})->purpose('Revisar propuestas vencidas y avisar de conflictos urgentes');
Schedule::command('agenda:review')->everyMinute()->withoutOverlapping(10);

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
