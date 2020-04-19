<?php

namespace Thenpingme;

use Illuminate\Console\Scheduling\ScheduleRunCommand;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Thenpingme\Client\Client;
use Thenpingme\Client\ThenpingmeClient;
use Thenpingme\Console\Commands\ScheduleRunCommand as ThenpingmeScheduleRunCommand;
use Thenpingme\Console\Commands\ThenpingmeScheduleListCommand;
use Thenpingme\Console\Commands\ThenpingmeSetupCommand;
use Thenpingme\Console\Commands\ThenpingmeSyncCommand;
use Thenpingme\Console\Commands\ThenpingmeVerifyCommand;
use Thenpingme\Signer\Signer;
use Thenpingme\Signer\ThenpingmeSigner;

class ThenpingmeServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/thenpingme.php' => config_path('thenpingme.php'),
            ], 'thenpingme-config');

            $this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/thenpingme'),
            ], 'thenpingme-lang');

            $this->commands([
                ThenpingmeSetupCommand::class,
                ThenpingmeScheduleListCommand::class,
                ThenpingmeVerifyCommand::class,
                ThenpingmeSyncCommand::class,
            ]);

            $this->app->extend(ScheduleRunCommand::class, function () {
                return new ThenpingmeScheduleRunCommand;
            });

            Event::subscribe(ScheduledTaskSubscriber::class);
        }

        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'thenpingme');
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
