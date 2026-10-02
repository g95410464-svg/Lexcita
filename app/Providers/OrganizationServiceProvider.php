<?php

namespace App\Providers;

use App\Support\OrganizationDeployment;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class OrganizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OrganizationDeployment::class);
        $this->app->make(OrganizationDeployment::class)->configure();
    }

    public function boot(): void
    {
        if ($this->app->make(OrganizationDeployment::class)->enabled()) {
            URL::forceRootUrl(config('app.url'));
            URL::forceScheme('https');
        }
    }
}
