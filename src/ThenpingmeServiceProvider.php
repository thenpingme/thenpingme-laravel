<?php

namespace Thenpingme;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Thenpingme\Console\Commands\ThenpingmeSetupCommand;

class ThenpingmeServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/thenpingme.php' => config_path('thenpingme.php'),
            ], 'config');

            $this->commands([
                ThenpingmeSetupCommand::class,
            ]);

            Event::subscribe(ScheduledTaskSubscriber::class);
        }
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/thenpingme.php', 'thenpingme');
        $this->mergeConfigFrom(__DIR__.'/../config/webhook-server.php', 'webhook-server');

        $this->app->singleton('thenpingme', function () {
            return new Thenpingme;
        });
    }
}
