<?php

namespace Thenpingme;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Thenpingme\Client\Client;
use Thenpingme\Client\ThenpingmeClient;
use Thenpingme\Console\Commands\ThenpingmeScheduleListCommand;
use Thenpingme\Console\Commands\ThenpingmeSetupCommand;
use Thenpingme\Console\Commands\ThenpingmeSyncCommand;
use Thenpingme\Console\Commands\ThenpingmeVerifyCommand;
use Thenpingme\Signer\Signer;
use Thenpingme\Signer\ThenpingmeSigner;

class ThenpingmeServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-thenpingme')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasCommands([                 
                ThenpingmeSetupCommand::class,
                ThenpingmeScheduleListCommand::class,
                ThenpingmeVerifyCommand::class,
                ThenpingmeSyncCommand::class,
            ]);
    }

    public function bootingPackage()
    {
        if ($this->app->runningInConsole()) {
            $this->app['events']->subscribe(ScheduledTaskSubscriber::class);
        }
    }

    public function packageRegistered()
    {
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
