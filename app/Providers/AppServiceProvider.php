<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventSilentlyDiscardingAttributes(! $this->app->environment('production'));
        Model::preventAccessingMissingAttributes(! $this->app->environment('production'));
        Model::preventLazyLoading(! $this->app->environment('production'));
        Model::unguard();
    }
}
