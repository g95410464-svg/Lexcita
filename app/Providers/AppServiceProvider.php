<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use App\Services\CitaService;
use App\Services\HorarioService;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CitaService::class);
        $this->app->singleton(HorarioService::class);
    }

    public function boot(): void
    {
        // Forzar HTTPS bajo proxy (Railway) para evitar errores de Mixed Content.
        if (app()->environment('production') || request()->header('x-forwarded-proto') === 'https') {
            URL::forceScheme('https');
        }
    }
}
