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

        $this->mergeConfigFrom(
            __DIR__.'/../config/meloquent.php', 'meloquent'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if($this->app->runningInConsole()) {
            $this->registerPublishing();
        }

        /** listening when a query executes */
        // DB::listen(function (QueryExecuted $query) {
        //     $query->sql;
        //     $query->bindings;
        //     $query->time;
        //     $query->toRawSql();
        // });

        // dd(config('meloquent.depth'));
    }

    protected function registerPublishing(): void
    {
        /** package publishable files */
        $this->publishes([
            __DIR__.'/../config/meloquent.php' => config_path('meloquent.php'),
        ], 'meloquent-config');

        $this->publishes([
            __DIR__.'/../Models/BaseNonPersistanceModel.php'
        ], 'BaseNonPersistanceModel');
    }

}
