<?php

namespace SajedZarinpour\Meloquent\Providers;

use Illuminate\Support\ServiceProvider;
use SajedZarinpour\Meloquent\Meloquent;

class MeloquentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        /** binding the melloquent */
        $this->app->bind('meloquent', function(){
            return new Meloquent([]);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        /** package publishable files */
        $this->publishes([
            __DIR__.'/../config/mellonquent.php' => config_path('mellonquent.php'),
        ]);
    }
}
