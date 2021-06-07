<?php

namespace Thenpingme;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use Thenpingme\Client\Client;
use Thenpingme\Client\ThenpingmeClient;
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
        }
    }

    public function bootingPackage(): void
    {
        if ($this->app->runningInConsole()) {
            $this->app['events']->subscribe(ScheduledTaskSubscriber::class);
        }

        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'thenpingme');
    }

    public function packageRegistered(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/thenpingme.php', 'thenpingme');

        $this->app->singleton('thenpingme', function () {
            return new Thenpingme;
        });

        $this->app->singleton(Signer::class, function (Application $app): ThenpingmeSigner {
            return $app->make(ThenpingmeSigner::class);
        });

        $this->app->singleton(Client::class, function (Application $app): Client {
            return $app->make(ThenpingmeClient::class);
        });
    }
}
