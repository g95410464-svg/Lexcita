<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\CitaService;
use App\Services\HorarioService;
use App\Services\WhatsAppService;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CitaService::class);
        $this->app->singleton(HorarioService::class);
        $this->app->singleton(WhatsAppService::class);
    }

    public function boot(): void
    {
        //
    }
}
