<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use App\Services\CitaService;
use App\Services\HorarioService;
use App\Support\SliSiteContent;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CitaService::class);
        $this->app->singleton(HorarioService::class);
        $this->app->singleton(\App\Support\AgendaModule::class);
        $this->app->singleton(\App\Services\Agenda\Routing\OpenRouteServiceProvider::class);
    }

    public function boot(): void
    {
        // Organization identity comes from server configuration, never the request.
        if (config('organization.id') === 'servicios-legales-integrados') {
            $this->app['view']->getFinder()->prependLocation(resource_path('views/sli'));
            $this->app['view']->composer(['sli.landing', 'sli.public.*'], function ($view): void {
                $view->with('sliSiteContent', SliSiteContent::get());
            });
        }

        // Forzar HTTPS bajo proxy (Railway) para evitar errores de Mixed Content.
        if (app()->environment('production') || request()->header('x-forwarded-proto') === 'https') {
            URL::forceScheme('https');
        }
    }
}
