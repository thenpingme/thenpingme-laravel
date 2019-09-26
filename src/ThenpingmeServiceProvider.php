<?php

namespace Thenpingme\Laravel;

use Illuminate\Support\ServiceProvider;

class ThenPingMeServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/thenpingme.php' => config_path('thenpingme.php'),
            ], 'config');

            // Registering package commands.
            // $this->commands([]);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/thenpingme.php', 'thenpingme');

        // Register the main class to use with the facade
        $this->app->singleton('thenpingme', function () {
            return new Thenpingme;
        });
    }
}
