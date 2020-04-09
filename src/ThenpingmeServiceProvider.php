<?php

namespace Thenpingme;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Thenpingme\Client\Client;
use Thenpingme\Client\TestClient;
use Thenpingme\Client\ThenpingmeClient;
use Thenpingme\Console\Commands\ThenpingmeSetupCommand;
use Thenpingme\Signer\Signer;
use Thenpingme\Signer\ThenpingmeSigner;

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

        $this->app->singleton('thenpingme', function () {
            return new Thenpingme;
        });

        $this->app->singleton(Signer::class, function ($app) {
            return $app->make(ThenpingmeSigner::class);
        });

        $this->app->singleton(Client::class, function ($app) {
            return $app->make(ThenpingmeClient::class);
        });
    }
}
